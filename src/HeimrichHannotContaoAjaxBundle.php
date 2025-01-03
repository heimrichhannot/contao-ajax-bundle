<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle;

use HeimrichHannot\AjaxBundle\DependencyInjection\AjaxExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoAjaxBundle extends Bundle
{
    public function getContainerExtension(): AjaxExtension
    {
        return new AjaxExtension();
    }
}
