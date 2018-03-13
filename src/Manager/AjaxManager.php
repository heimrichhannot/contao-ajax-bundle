<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Manager;

use Contao\System;
use HeimrichHannot\AjaxBundle\Response\Response;
use HeimrichHannot\AjaxBundle\Response\ResponseError;

class AjaxManager
{
    const AJAX_ATTR_SCOPE = 'as';
    const AJAX_ATTR_ACT = 'aa';
    const AJAX_ATTR_GROUP = 'ag';
    const AJAX_ATTR_TYPE = 'at';
    const AJAX_ATTR_AJAXID = 'aid';
    const AJAX_ATTR_TOKEN = 'ato';

    const AJAX_SCOPE_DEFAULT = 'ajax';
    const AJAX_TYPE_MODULE = 'module';

    const AJAX_ERROR_INVALID_GROUP = 1;
    const AJAX_ERROR_NO_AVAILABLE_ACTIONS = 2;
    const AJAX_ERROR_INVALID_ACTION = 3;
    const AJAX_ERROR_INVALID_TOKEN = 4;

    /**
     * Determine if the current ajax request is group related.
     *
     * @param string $groupRequested The ajax group
     *
     * @return bool True / False if group from request match, otherwise null (no ajax request)
     */
    public function isRelated(string $groupRequested)
    {
        if (System::getContainer()->get('huh.request')->isXmlHttpRequest()) {
            if (null === ($strGroup = $this->getActiveGroup($groupRequested))) {
                return false;
            }

            return true;
        }

        return null;
    }

    /**
     * Trigger a valid ajax action.
     *
     * @param string $group
     * @param string $action
     * @param        $objContext
     */
    public function runActiveAction(string $group, string $action, $objContext)
    {
        if (System::getContainer()->get('huh.request')->isXmlHttpRequest()) {
            // Add custom logic via hook
            if (isset($GLOBALS['TL_HOOKS']['beforeAjaxAction']) && is_array($GLOBALS['TL_HOOKS']['beforeAjaxAction'])) {
                foreach ($GLOBALS['TL_HOOKS']['beforeAjaxAction'] as $callback) {
                    if (is_array($callback)) {
                        $callbackObj = System::importStatic($callback[0]);
                        $callbackObj->{$callback[1]}($group, $action, $objContext);
                    } elseif (is_callable($callback)) {
                        $callback($group, $action, $objContext);
                    }
                }
            }

            /** @var AjaxActionManager */
            $objAction = self::getActiveAction($group, $action);

            if ($objAction === static::AJAX_ERROR_INVALID_GROUP) {
                $objResponse = new ResponseError('Invalid ajax group.');
                $objResponse->send();
                exit;
            }

            if ($objAction === static::AJAX_ERROR_NO_AVAILABLE_ACTIONS) {
                $objResponse = new ResponseError('No available ajax actions within given group.');
                $objResponse->send();
                exit;
            }

            if ($objAction === static::AJAX_ERROR_INVALID_ACTION) {
                $objResponse = new ResponseError('Invalid ajax act.');
                $objResponse->send();
                exit;
            } elseif ($objAction === static::AJAX_ERROR_INVALID_TOKEN) {
                $objResponse = new ResponseError('Invalid ajax token.');
                $objResponse->send();
                exit;
            }

            if (null !== $objAction) {
                $objResponse = $objAction->call($objContext);

                /* @var Response */
                if ($objResponse instanceof Response) {
                    // remove used ajax tokens
                    if (null !== ($strToken = System::getContainer()->get('huh.ajax.token')->getActiveToken())) {
                        System::getContainer()->get('huh.ajax.token')->remove($strToken);
                    }

                    $objResponse->output();
                }
            }
        }
    }

    /**
     * Get the active ajax action object.
     *
     * @param string $strGroupRequested Requested ajax group
     *
     * @return string The name of the active group, otherwise null
     */
    public function getActiveGroup(string $strGroupRequested)
    {
        $strScope = System::getContainer()->get('huh.request')->getGet(static::AJAX_ATTR_SCOPE);
        $strGroup = System::getContainer()->get('huh.request')->getGet(static::AJAX_ATTR_GROUP);

        if ($strScope !== static::AJAX_SCOPE_DEFAULT) {
            return null;
        }

        if (!$strGroup) {
            return null;
        }

        if ($strGroupRequested !== $strGroup) {
            return null;
        }

        return $strGroup;
    }

    /**
     * Get the active ajax action object.
     *
     * @param string $groupRequested  Requested ajax group
     * @param string $actionRequested Requested ajax action within group
     *
     * @return AjaxActionManager|int A valid AjaxAction | null if the action is not a registered ajax action
     */
    public function getActiveAction(string $groupRequested, string $actionRequested)
    {
        $strAct = System::getContainer()->get('huh.request')->getGet(static::AJAX_ATTR_ACT);
        $strToken = System::getContainer()->get('huh.request')->getGet(static::AJAX_ATTR_TOKEN);

        if (!$strAct) {
            return null;
        }

        if (null === ($strGroup = $this->getActiveGroup($groupRequested))) {
            return null;
        }

        if ($actionRequested !== $strAct) {
            return null;
        }

        $arrConfig = $GLOBALS['AJAX'];

        if (!is_array($arrConfig)) {
            return null;
        }

        if (!isset($arrConfig[$strGroup])) {
            return static::AJAX_ERROR_INVALID_GROUP;
        }

        if (!is_array($arrConfig[$strGroup]['actions'])) {
            return static::AJAX_ERROR_NO_AVAILABLE_ACTIONS;
        }

        $arrActions = $arrConfig[$strGroup]['actions'];

        if (!array_key_exists($actionRequested, $arrActions)) {
            return static::AJAX_ERROR_INVALID_ACTION;
        }

        $arrAttributes = $arrActions[$strAct];

        // ajax request token check
        if (!isset($arrAttributes['csrf_protection']) && (!$strToken || !System::getContainer()->get('huh.ajax.token')->validate($strToken))) {
            return static::AJAX_ERROR_INVALID_TOKEN;
        }

        return new AjaxActionManager($strGroup, $strAct, $arrAttributes, $strToken);
    }

    /**
     * Set new request token and set expired state within $_POST as param REQUEST_TOKEN_EXPIRED.
     */
    public function setRequestTokenExpired()
    {
        \RequestToken::initialize();
        $_POST['REQUEST_TOKEN_EXPIRED'] = true;
        $_POST['REQUEST_TOKEN'] = \RequestToken::get();
        System::getContainer()->get('huh.request')->setPost('REQUEST_TOKEN', \RequestToken::get());
        System::getContainer()->get('huh.request')->setPost('REQUEST_TOKEN_EXPIRED', true);
    }

    /**
     * Return true if the request token has expired in between.
     *
     * @return mixed
     */
    public function isRequestTokenExpired()
    {
        return System::getContainer()->get('huh.utils.container')->isFrontend() && System::getContainer()->get('huh.request')->isXmlHttpRequest() && System::getContainer()->get('huh.request')->getPost('REQUEST_TOKEN_EXPIRED');
    }
}
