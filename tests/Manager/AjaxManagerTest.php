<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Manager;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;
use HeimrichHannot\AjaxBundle\Exception\InvalidAjaxActException;
use HeimrichHannot\AjaxBundle\Exception\InvalidAjaxGroupException;
use HeimrichHannot\AjaxBundle\Exception\InvalidAjaxTokenException;
use HeimrichHannot\AjaxBundle\Exception\NoAjaxActionWithinGroupException;
use HeimrichHannot\AjaxBundle\Manager\AjaxActionManager;
use HeimrichHannot\AjaxBundle\Manager\AjaxManager;
use HeimrichHannot\AjaxBundle\Manager\AjaxTokenManager;
use HeimrichHannot\AjaxBundle\Response\ResponseSuccess;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AjaxManagerTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (!defined('TL_ROOT')) {
            define('TL_ROOT', '');
        }

        $container = $this->mockContainer();
        $request = $this->mockAdapter(['isXmlHttpRequest', 'getGet', 'getSession', 'hasSession', 'getPost']);
        $request->method('isXmlHttpRequest')->willReturn(true);
        $request->method('getGet')->willReturnCallback(function ($param) {
            switch ($param) {
                case AjaxManager::AJAX_ATTR_SCOPE === $param:
                    return AjaxManager::AJAX_SCOPE_DEFAULT;
                    break;
                case AjaxManager::AJAX_ATTR_ACT === $param:
                    return 'getTrue';
                    break;
                default:
                    return $param;
                    break;
            }
        });
        $request->method('getSession')->willReturn(new Session(new MockArraySessionStorage()));
        $request->method('hasSession')->willReturn(true);
        $request->method('getPost')->willReturn(true);

        $utilsContainer = $this->mockAdapter(['isFrontend']);
        $utilsContainer->method('isFrontend')->willReturn(true);

        $container->set('huh.request', $request);
        $container->set('huh.utils.container', $utilsContainer);
        System::setContainer($container);

        $container->set('huh.ajax.token', new AjaxTokenManager());
        System::setContainer($container);

        if (!\function_exists('ampersand')) {
            include_once __DIR__.'/../../vendor/contao/core-bundle/src/Resources/contao/helper/functions.php';
        }
    }

    public function testIsRelated()
    {
        $manager = new AjaxManager();
        $result = $manager->isRelated('ag');
        $this->assertTrue($result);

        $result = $manager->isRelated('test');
        $this->assertFalse($result);

        $container = System::getContainer();
        $request = $this->mockAdapter(['isXmlHttpRequest', 'getGet']);
        $request->method('isXmlHttpRequest')->willReturn(false);
        $container->set('huh.request', $request);

        System::setContainer($container);

        $result = $manager->isRelated('test');
        $this->assertNull($result);
    }

    public function testGetActiveGroup()
    {
        $manager = new AjaxManager();

        $this->assertSame('ag', $manager->getActiveGroup('ag'));
        $this->assertNull($manager->getActiveGroup('null'));

        $container = System::getContainer();
        $request = $this->mockAdapter(['isXmlHttpRequest', 'getGet']);
        $request->method('getGet')->willReturnCallback(function ($param) {
            switch ($param) {
                case AjaxManager::AJAX_ATTR_SCOPE === $param:
                    return $param;
                    break;
                default:
                    return false;
                    break;
            }
        });
        $container->set('huh.request', $request);

        System::setContainer($container);

        $this->assertNull($manager->getActiveGroup('null'));

        $container = System::getContainer();
        $request = $this->mockAdapter(['isXmlHttpRequest', 'getGet']);
        $request->method('getGet')->willReturnCallback(function ($param) {
            switch ($param) {
                case AjaxManager::AJAX_ATTR_SCOPE === $param:
                    return AjaxManager::AJAX_SCOPE_DEFAULT;
                    break;
                default:
                    return false;
                    break;
            }
        });
        $container->set('huh.request', $request);

        System::setContainer($container);

        $this->assertNull($manager->getActiveGroup('null'));
    }

    public function testGetActiveAction()
    {
        $GLOBALS['AJAX'] = null;

        $manager = new AjaxManager();
        $this->assertNull($manager->getActiveAction('group', 'action'));
        $this->assertNull($manager->getActiveAction('ag', 'action'));
        $this->assertNull($manager->getActiveAction('ag', 'getTrue'));

        $GLOBALS['AJAX'] = [];
        $this->assertSame(1, $manager->getActiveAction('ag', 'getTrue'));

        $GLOBALS['AJAX'] = ['ag' => ['actions' => '']];
        $this->assertSame(2, $manager->getActiveAction('ag', 'getTrue'));

        $GLOBALS['AJAX'] = ['ag' => ['actions' => []]];
        $this->assertSame(3, $manager->getActiveAction('ag', 'getTrue'));

        $GLOBALS['AJAX'] = ['ag' => ['actions' => ['getTrue' => 'action']]];
        $this->assertSame(4, $manager->getActiveAction('ag', 'getTrue'));

        $GLOBALS['AJAX'] = ['ag' => ['actions' => ['getTrue' => ['csrf_protection' => true]]]];
        $this->assertInstanceOf(AjaxActionManager::class, $manager->getActiveAction('ag', 'getTrue'));

        $container = System::getContainer();
        $request = $this->mockAdapter(['isXmlHttpRequest', 'getGet']);
        $request->method('getGet')->willReturnCallback(function ($param) {
            switch ($param) {
                case AjaxManager::AJAX_ATTR_SCOPE === $param:
                    return AjaxManager::AJAX_SCOPE_DEFAULT;
                    break;
                default:
                    return false;
                    break;
            }
        });
        $container->set('huh.request', $request);

        System::setContainer($container);
        $this->assertNull($manager->getActiveAction('ag', 'getTrue'));
    }

    public function testSetRequestTokenExpired()
    {
        $container = System::getContainer();
        $requestStack = new RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request());

        $backendMatcher = new RequestMatcher('/contao', 'test.com', null, ['192.168.1.0']);
        $frontendMatcher = new RequestMatcher('/index', 'test.com', null, ['192.168.1.0']);

        $scopeMatcher = new ScopeMatcher($backendMatcher, $frontendMatcher);

        $tokenAdapter = $this->mockAdapter(['getToken', 'getValue']);
        $tokenAdapter->method('getToken')->willReturnSelf();
        $tokenAdapter->method('getValue')->willReturn('token');

        $request = new Request($this->mockContaoFramework(), $requestStack, $scopeMatcher);
        $container->set('huh.request', $request);
        $container->set('contao.csrf.token_manager', $tokenAdapter);
        System::setContainer($container);

        $manager = new AjaxManager();
        $manager->setRequestTokenExpired();

        $this->assertTrue($request->get('REQUEST_TOKEN_EXPIRED'));
        $this->assertSame('token', $request->get('REQUEST_TOKEN'));
    }

    public function testUsRequestTokenExpired()
    {
        $manager = new AjaxManager();
        $this->assertTrue($manager->isRequestTokenExpired());
    }

    public function testRunActiveAction()
    {
        $manager = new AjaxManager();
        try {
            $GLOBALS['AJAX'] = [];
            $manager->runActiveAction('ag', 'getTrue', 'test');
        } catch (InvalidAjaxGroupException $exception) {
            $this->assertSame('Invalid ajax group.', $exception->getMessage());
        }

        try {
            $GLOBALS['AJAX'] = ['ag' => ['actions' => '']];
            $manager->runActiveAction('ag', 'getTrue', 'test');
        } catch (NoAjaxActionWithinGroupException $exception) {
            $this->assertSame('No available ajax actions within given group.', $exception->getMessage());
        }

        try {
            $GLOBALS['AJAX'] = ['ag' => ['actions' => []]];
            $manager->runActiveAction('ag', 'getTrue', 'test');
        } catch (InvalidAjaxActException $exception) {
            $this->assertSame('Invalid ajax act.', $exception->getMessage());
        }

        try {
            $GLOBALS['AJAX'] = ['ag' => ['actions' => ['getTrue' => 'action']]];
            $manager->runActiveAction('ag', 'getTrue', 'test');
        } catch (InvalidAjaxTokenException $exception) {
            $this->assertSame('Invalid ajax token.', $exception->getMessage());
        }

        $requestStack = new RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request());

        $backendMatcher = new RequestMatcher('/contao', 'test.com', null, ['192.168.1.0']);
        $frontendMatcher = new RequestMatcher('/index', 'test.com', null, ['192.168.1.0']);

        $scopeMatcher = new ScopeMatcher($backendMatcher, $frontendMatcher);

        $tokenAdapter = $this->mockAdapter(['getToken', 'getValue']);
        $tokenAdapter->method('getToken')->willReturnSelf();
        $tokenAdapter->method('getValue')->willReturn('token');

        $request = new Request($this->mockContaoFramework(), $requestStack, $scopeMatcher);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setGet(AjaxManager::AJAX_ATTR_ACT, 'getResponse');
        $request->setGet(AjaxManager::AJAX_ATTR_TOKEN, 'ag');
        $request->setGet(AjaxManager::AJAX_ATTR_SCOPE, 'ajax');
        $request->setGet(AjaxManager::AJAX_ATTR_GROUP, 'ag');

        $token = $this->mockAdapter(['getActiveToken', 'remove', 'create']);
        $token->method('getActiveToken')->willReturn('token');
        $token->method('create')->willReturn('token');
        $container = System::getContainer();
        $container->set('huh.request', $request);
        $container->set('huh.ajax.token', $token);
        System::setContainer($container);

        $GLOBALS['AJAX'] = ['ag' => ['actions' => ['getResponse' => ['csrf_protection' => true]]]];
        try {
            ob_start();
            $manager->runActiveAction('ag', 'getResponse', $this);
        } catch (AjaxExitException $exception) {
            $this->assertSame('exit', $exception->getMessage());
        }
    }

    /**
     * @return ResponseSuccess
     */
    public function getResponse()
    {
        return new ResponseSuccess();
    }
}
