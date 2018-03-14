<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Manager;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;
use HeimrichHannot\AjaxBundle\Manager\AjaxActionManager;
use HeimrichHannot\AjaxBundle\Manager\AjaxManager;
use HeimrichHannot\AjaxBundle\Response\ResponseSuccess;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Classes\ClassUtil;
use HeimrichHannot\UtilsBundle\String\StringUtil;
use HeimrichHannot\UtilsBundle\Url\UrlUtil;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class AjaxActionManagerTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        $request = $this->mockAdapter(['getGet']);
        $request->method('getGet')->willReturn(false);

        $token = $this->mockAdapter(['validate', 'create']);
        $token->method('validate')->willReturn(false);
        $token->method('create')->willReturn('token');

        $container = $this->mockContainer();
        $container->set('huh.utils.url', new UrlUtil($this->mockContaoFramework()));
        $container->set('huh.utils.class', new ClassUtil());
        $container->set('huh.utils.array', new ArrayUtil($this->mockContaoFramework()));
        $container->set('huh.utils.string', new StringUtil($this->mockContaoFramework()));
        $container->set('huh.request', $request);
        $container->set('huh.ajax.token', $token);
        $container->set('huh.ajax', new AjaxManager());
        System::setContainer($container);

        if (!\function_exists('ampersand')) {
            include_once __DIR__.'/../../vendor/contao/core-bundle/src/Resources/contao/helper/functions.php';
        }
    }

    public function testRemoveAjaxParametersFromUrl()
    {
        $manager = new AjaxActionManager('aa', 'action');

        $this->assertSame('https://heimrich-hannot.com', $manager->removeAjaxParametersFromUrl('https://heimrich-hannot.com?aa=test'));
    }

    public function testGetParams()
    {
        $GLOBALS['AJAX']['ag']['actions']['testAction'] = ['csrf_protection' => true];

        $manager = new AjaxActionManager('ag', 'aa');
        $this->assertSame(['as' => 'ajax', 'ag' => 'ag', 'aa' => 'testAction', 'ato' => 'token'], $manager->getParams('ag', 'testAction'));
        $this->assertSame(['as' => 'ajax', 'ag' => 'ag'], $manager->getParams('ag'));
    }

    public function testCall()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request());

        $backendMatcher = new RequestMatcher('/contao', 'test.com', null, ['192.168.1.0']);
        $frontendMatcher = new RequestMatcher('/index', 'test.com', null, ['192.168.1.0']);

        $scopeMatcher = new ScopeMatcher($backendMatcher, $frontendMatcher);

        $tokenAdapter = $this->mockAdapter(['getToken', 'getValue']);
        $tokenAdapter->method('getToken')->willReturnSelf();
        $tokenAdapter->method('getValue')->willReturn('token');

        $request = new Request($this->mockContaoFramework(), $requestStack, $scopeMatcher);
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

        try {
            $manager = new AjaxActionManager('ag', 'getResponse');
            $manager->call(null);
        } catch (\Exception $exception) {
            $this->assertSame('Bad Request, context not set.', $exception->getMessage());
        }

        try {
            $manager = new AjaxActionManager('ag', 'test');
            $manager->call($this);
        } catch (\Exception $exception) {
            $this->assertSame('Bad Request, ajax method does not exist within context.', $exception->getMessage());
        }

        try {
            $manager = new AjaxActionManager('ag', 'getProtectedResponse');
            $manager->call($this);
        } catch (\Exception $exception) {
            $this->assertSame('Bad Request, the called method is not public.', $exception->getMessage());
        }

        $manager = new AjaxActionManager('ag', 'getResponse');
        $this->assertInstanceOf(ResponseSuccess::class, $manager->call($this));
    }

    public function testGetArguments()
    {
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

        $token = $this->mockAdapter(['getActiveToken', 'remove', 'create']);
        $token->method('getActiveToken')->willReturn('token');
        $token->method('create')->willReturn('token');
        $container = System::getContainer();
        $container->set('huh.request', $request);
        $container->set('huh.ajax.token', $token);
        System::setContainer($container);

        $manager = new AjaxActionManager('ag', 'getResponse', ['arguments' => [true, 'test']]);

        try {
            $function = $this->getMethod(AjaxActionManager::class, 'getArguments');
            $function->invoke($manager);
        } catch (AjaxExitException $exception) {
            $this->assertStringStartsWith('Bad Request, missing argument ', $exception->getMessage());
        }

        $manager = new AjaxActionManager('ag', 'getResponse', ['arguments' => [true, 'aa']]);
        $function = $this->getMethod(AjaxActionManager::class, 'getArguments');
        $this->assertSame([true, false], $function->invoke($manager));
    }

    public function testGenerateUrl()
    {
        global $objPage;

        $objPage = $this->mockClassWithProperties(PageModel::class, []);
        $objPage->method('row')->willReturn(['row']);

        $controller = $this->mockAdapter(['generateFrontendUrl']);
        $controller->method('generateFrontendUrl')->willReturn('url');

        $url = $this->mockAdapter(['addQueryString']);
        $url->method('addQueryString')->willReturn('url');

        $container = System::getContainer();
        $container->set('huh.utils.url', $url);
        System::setContainer($container);

        $manager = new AjaxActionManager('ag', 'test');
        $this->assertSame('url', $manager->generateUrl('ag', 'test'));
    }

    /**
     * @return ResponseSuccess
     */
    public function getResponse()
    {
        return new ResponseSuccess();
    }

    protected function getMethod($class, $name)
    {
        $class = new \ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @return ResponseSuccess
     */
    protected function getProtectedResponse()
    {
        return new ResponseSuccess();
    }
}
