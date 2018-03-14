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
use HeimrichHannot\AjaxBundle\Manager\AjaxManager;
use HeimrichHannot\AjaxBundle\Manager\AjaxTokenManager;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class AjaxTokenManagerTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (!defined('TL_ROOT')) {
            define('TL_ROOT', '');
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
        $request->setGet(AjaxManager::AJAX_ATTR_ACT, 'false');
        $request->setGet(AjaxManager::AJAX_ATTR_TOKEN, 'ag');
        $request->setGet(AjaxManager::AJAX_ATTR_SCOPE, 'ajax');
        $request->setGet(AjaxManager::AJAX_ATTR_GROUP, 'ag');

        $container = $this->mockContainer();
        $container->set('huh.request', $request);
        $container->set('huh.utils.array', new ArrayUtil($this->mockContaoFramework()));
        System::setContainer($container);
    }

    public function testInstantiation()
    {
        $manager = new AjaxTokenManager();
        $this->assertInstanceOf(AjaxTokenManager::class, $manager);
    }

    public function testGet()
    {
        $manager = new AjaxTokenManager();
        $token = $manager->get();
        $this->assertNotNull($token);
        $this->assertSame(32, strlen($token[0]));
    }

    public function testRemove()
    {
        $manager = new AjaxTokenManager();
        $token = $manager->get();
        $manager->remove($token[0]);

        $newToken = $manager->get();
        $this->assertEmpty($newToken);
    }

    public function testCreate()
    {
        $manager = new AjaxTokenManager();
        $token = $manager->create();
        $this->assertSame(32, strlen($token));
    }

    public function testValidate()
    {
        $manager = new AjaxTokenManager();
        $token = $manager->get();
        $this->assertTrue($manager->validate($token[0]));
        $this->assertFalse($manager->validate(''));

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TL_CONFIG']['requestTokenWhitelist'] = ['127.0.0.1', 'localhost'];
        $this->assertTrue($manager->validate('token'));
    }

    public function testGetActiveToken()
    {
        $manager = new AjaxTokenManager();
        $this->assertNull($manager->getActiveToken());

        $token = $manager->get();
        System::getContainer()->get('huh.request')->setGet(AjaxManager::AJAX_ATTR_TOKEN, $token[0]);
        $this->assertSame($token[0], $manager->getActiveToken());
    }
}
