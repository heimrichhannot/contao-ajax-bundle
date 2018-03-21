<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Backend;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Backend\Hooks;
use HeimrichHannot\AjaxBundle\Manager\AjaxManager;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class HooksTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        $containerUtils = $this->mockAdapter(['isBackend']);
        $containerUtils->method('isBackend')->willReturn(false);

        $tokenManager = $this->mockAdapter(['isTokenValid', 'getToken', 'getValue']);
        $tokenManager->method('isTokenValid')->willReturn(false);
        $tokenManager->method('getToken')->willReturnSelf();
        $tokenManager->method('getValue')->willReturn('token');

        $requestStack = new RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request());

        $backendMatcher = new RequestMatcher('/contao', 'test.com', null, ['192.168.1.0']);
        $frontendMatcher = new RequestMatcher('/index', 'test.com', null, ['192.168.1.0']);

        $scopeMatcher = new ScopeMatcher($backendMatcher, $frontendMatcher);

        $tokenAdapter = $this->mockAdapter(['getToken', 'getValue']);
        $tokenAdapter->method('getToken')->willReturnSelf();
        $tokenAdapter->method('getValue')->willReturn('token');

        $request = new Request($this->mockContaoFramework(), $requestStack, $scopeMatcher);
        $request->server->set('REQUEST_METHOD', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $container = $this->mockContainer();
        $container->set('huh.utils.container', $containerUtils);
        $container->setParameter('contao.csrf_token_name', 'token');
        $container->set('huh.request', $request);
        $container->set('huh.ajax', new AjaxManager());
        $container->set('security.csrf.token_manager', $tokenManager);
        System::setContainer($container);
    }

    public function testInitializeSystemHook()
    {
        $hook = new Hooks();
        $hook->initializeSystemHook();
        $this->assertSame('token', System::getContainer()->get('huh.request')->getPost('REQUEST_TOKEN'));

        $request = $this->mockAdapter(['isXmlHttpRequest', 'isMethod', 'getPost']);
        $request->method('isXmlHttpRequest')->willReturn(false);

        $containerUtils = $this->mockAdapter(['isBackend']);
        $containerUtils->method('isBackend')->willReturn(false);

        $container = $this->mockContainer();
        $container->set('huh.request', $request);
        $container->set('huh.utils.container', $containerUtils);
        System::setContainer($container);

        $hook->initializeSystemHook();

        $containerUtils = $this->mockAdapter(['isBackend']);
        $containerUtils->method('isBackend')->willReturn(true);

        $container = $this->mockContainer();
        $container->set('huh.utils.container', $containerUtils);
        System::setContainer($container);

        $hook->initializeSystemHook();

        $containerUtils = $this->mockAdapter(['isBackend']);
        $containerUtils->method('isBackend')->willReturn(false);

        $request = $this->mockAdapter(['isXmlHttpRequest', 'isMethod', 'getPost']);
        $request->method('isXmlHttpRequest')->willReturn(true);
        $request->method('isMethod')->willReturn(true);
        $request->method('getPost')->willReturn('token');

        $tokenManager = $this->mockAdapter(['isTokenValid']);
        $tokenManager->method('isTokenValid')->willReturn(true);

        $ajaxManager = $this->mockAdapter(['setRequestTokenExpired']);

        $container = $this->mockContainer();
        $container->set('huh.utils.container', $containerUtils);
        $container->setParameter('contao.csrf_token_name', 'token');
        $container->set('huh.request', $request);
        $container->set('huh.ajax', $ajaxManager);
        $container->set('security.csrf.token_manager', $tokenManager);
        System::setContainer($container);

        $hook->initializeSystemHook();
    }
}
