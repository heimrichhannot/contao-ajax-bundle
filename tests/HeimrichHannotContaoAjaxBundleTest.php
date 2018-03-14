<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests;

use HeimrichHannot\AjaxBundle\DependencyInjection\AjaxExtension;
use HeimrichHannot\AjaxBundle\HeimrichHannotContaoAjaxBundle;
use PHPUnit\Framework\TestCase;

class HeimrichHannotContaoAjaxBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new HeimrichHannotContaoAjaxBundle();

        $this->assertInstanceOf(AjaxExtension::class, $bundle->getContainerExtension());
    }
}
