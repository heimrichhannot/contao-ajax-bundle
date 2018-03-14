<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Backend;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Backend\Hooks;

class HooksTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        $containerUtils = $this->mockAdapter(['isBackend']);
        $containerUtils->method('isBackend')->willReturn(false);

        $request = $this->mockAdapter(['isXmlHttpRequest', 'isMethod', 'getPost']);
        $request->method('isXmlHttpRequest')->willReturn(true);
        $request->method('isMethod')->willReturn(true);
        $request->method('getPost')->willReturn('token');

        $tokenManager = $this->mockAdapter(['isTokenValid']);
        $tokenManager->method('isTokenValid')->willReturn(false);

        $ajaxManager = $this->mockAdapter(['setRequestTokenExpired']);

        $container = $this->mockContainer();
        $container->set('huh.utils.container', $containerUtils);
        $container->setParameter('contao.csrf_token_name', 'token');
        $container->set('huh.request', $request);
        $container->set('huh.ajax', $ajaxManager);
        $container->set('contao.csrf.token_manager', $tokenManager);
        System::setContainer($container);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testInitializeSystemHook()
    {
        $hook = new Hooks();
        $hook->initializeSystemHook();

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
        $container->set('contao.csrf.token_manager', $tokenManager);
        System::setContainer($container);

        $hook->initializeSystemHook();
    }
}
