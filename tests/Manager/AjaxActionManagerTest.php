<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Manager;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Manager\AjaxActionManager;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Classes\ClassUtil;
use HeimrichHannot\UtilsBundle\String\StringUtil;
use HeimrichHannot\UtilsBundle\Url\UrlUtil;

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
}
