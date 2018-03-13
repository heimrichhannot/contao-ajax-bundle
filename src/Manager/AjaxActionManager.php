<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Manager;

use Contao\Controller;
use Contao\PageModel;
use Contao\System;
use HeimrichHannot\AjaxBundle\Response\ResponseError;

class AjaxActionManager
{
    protected $strGroup;

    protected $strAction;

    protected $arrAttributes;

    protected $strToken;

    /**
     * AjaxAction constructor.
     *
     * @param string $group
     * @param string $action
     * @param array  $attributes
     * @param null   $token
     */
    public function __construct(string $group, string $action, array $attributes = [], $token = null)
    {
        $this->strGroup = $group;
        $this->strAction = $action;
        $this->arrAttributes = $attributes;
        $this->strToken = $token;
    }

    /**
     * @param string $url
     *
     * @return mixed
     */
    public function removeAjaxParametersFromUrl(string $url)
    {
        return System::getContainer()->get('huh.utils.url')->removeQueryString(System::getContainer()->get('huh.utils.class')->getConstantsByPrefixes('HeimrichHannot\AjaxBundle\Backend\AjaxManager', ['AJAX_ATTR']), $url);
    }

    /**
     * @param string      $group
     * @param string|null $action
     * @param array       $attributes
     * @param bool        $keepParams
     * @param string|null $url
     *
     * @return null|string
     */
    public function generateUrl(string $group, string $action = null, array $attributes = [], bool $keepParams = true, string $url = null)
    {
        /*
         * @var PageModel $objPage
         */
        global $objPage;

        if (null === $url) {
            $url = $keepParams ? null : System::getContainer()->get('contao.framework')->getAdapter(Controller::class)->generateFrontendUrl($objPage->row(), null, null, true);
        }

        $url = System::getContainer()->get('huh.utils.url')->addQueryString(http_build_query(static::getParams($group, $action), '', '&'), $url);
        $url = System::getContainer()->get('huh.utils.url')->addQueryString(http_build_query($attributes, '', '&'), $url);

        return $url;
    }

    /**
     * @param string      $group
     * @param string|null $action
     *
     * @return array
     */
    public function getParams(string $group, string $action = null)
    {
        $arrParams = [
            AjaxManager::AJAX_ATTR_SCOPE => AjaxManager::AJAX_SCOPE_DEFAULT,
            AjaxManager::AJAX_ATTR_GROUP => $group,
        ];

        if (null !== $action) {
            $arrParams[AjaxManager::AJAX_ATTR_ACT] = $action;
        }

        $arrConfig = $GLOBALS['AJAX'][$group]['actions'][$action];

        if ($arrConfig && $arrConfig['csrf_protection']) {
            $strToken = System::getContainer()->get('huh.request')->getGet(AjaxManager::AJAX_ATTR_TOKEN);

            // create a new token for each action
            if (!$strToken || ($strToken && !System::getContainer()->get('huh.ajax.token')->validate($strToken))) {
                $arrParams[AjaxManager::AJAX_ATTR_TOKEN] = System::getContainer()->get('huh.ajax.token')->create();
            }
        }

        return $arrParams;
    }

    /**
     * @param $objContext
     *
     * @return mixed
     */
    public function call($objContext)
    {
        $objItem = null;

        if (null === $objContext) {
            $objResponse = new ResponseError('Bad Request, context not set.');
            $objResponse->send();
            exit;
        }

        if (!method_exists($objContext, $this->strAction)) {
            $objResponse = new ResponseError('Bad Request, ajax method does not exist within context.');
            $objResponse->send();
            exit;
        }

        $reflection = new \ReflectionMethod($objContext, $this->strAction);

        if (!$reflection->isPublic()) {
            $objResponse = new ResponseError('Bad Request, the called method is not public.');
            $objResponse->send();
            exit;
        }

        return call_user_func_array([$objContext, $this->strAction], $this->getArguments());
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        $arrArgumentValues = [];
        $arrArguments = $this->arrAttributes['arguments'];
        $arrOptional = is_array($this->arrAttributes['optional']) ? $this->arrAttributes['optional'] : [];

        $arrCurrentArguments = System::getContainer()->get('huh.request')->isMethod('POST') ? System::getContainer()->get('huh.request')->request->all() : System::getContainer()->get('huh.request')->query->all();

        foreach ($arrArguments as $argument) {
            if (is_array($argument) || is_bool($argument)) {
                $arrArgumentValues[] = $argument;
                continue;
            }

            if (count(preg_grep('/'.$argument.'/i', $arrOptional)) < 1 && count(preg_grep('/'.$argument.'/i', array_keys($arrCurrentArguments))) < 1) {
                $objResponse = new ResponseError('Bad Request, missing argument '.$argument);
                $objResponse->send();
                exit;
            }

            $varValue = System::getContainer()->get('huh.request')->isMethod('POST') ? System::getContainer()->get('huh.request')->getPost($argument) : System::getContainer()->get('huh.request')->getGet($argument);

            if ('true' === $varValue || 'false' === $varValue) {
                $varValue = filter_var($varValue, FILTER_VALIDATE_BOOLEAN);
            }

            $arrArgumentValues[] = $varValue;
        }

        return $arrArgumentValues;
    }
}
