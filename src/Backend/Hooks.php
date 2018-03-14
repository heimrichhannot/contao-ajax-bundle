<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Backend;

use Contao\System;
use Symfony\Component\Security\Csrf\CsrfToken;

class Hooks
{
    /**
     * Contao initialize.php hook before request token validation happend.
     */
    public function initializeSystemHook()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            return;
        }

        if (!System::getContainer()->get('huh.request')->isXmlHttpRequest()) {
            return;
        }

        // improved REQUEST_TOKEN handling within front end mode
        if (System::getContainer()->get('huh.request')->isMethod('POST') && !System::getContainer()->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken(System::getContainer()->getParameter('contao.csrf_token_name'), System::getContainer()->get('huh.request')->getPost('REQUEST_TOKEN')))) {
            System::getContainer()->get('huh.ajax')->setRequestTokenExpired();
        }
    }
}
