<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Manager;

use Contao\System;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\HttpFoundation\Request;

class AjaxActionManager
{
    /**
     * @param null $strToken
     */
    public function __construct(
        protected string $strGroup = '',
        protected string $strAction = '',
        protected array $arrAttributes = [],
        protected ?string $strToken = null,
    ) {
    }

    public function removeAjaxParametersFromUrl(string $url): ?string
    {
        foreach (AjaxManager::AJAX_ATTRIBUTES as $attribute) {
            $url = System::getContainer()->get(Utils::class)->url()
                ->removeQueryStringParameterFromUrl($attribute, $url);
        }

        return $url;
    }

    public function generateUrl(
        string $group,
        ?string $action = null,
        array $attributes = [],
        bool $keepParams = true,
        ?string $url = null,
    ): ?string {
        /* @var PageModel $objPage */
        global $objPage;

        if (null === $url) {
            $url = $keepParams ? null : $objPage->getFrontendUrl();
        }

        // todo: modernize, getFrontendUrl is deprecated

        /** @var Utils $utils */
        $utils = System::getContainer()->get(Utils::class);

        $url = $utils->url()->addQueryStringParameterToUrl(http_build_query($this->getParams($group, $action), '', '&'), $url ?: '');
        $url = $utils->url()->addQueryStringParameterToUrl(http_build_query($attributes, '', '&'), $url);

        return $url;
    }

    public function getParams(string $group, ?string $action = null): array
    {
        $arrParams = [
            AjaxManager::AJAX_ATTR_SCOPE => AjaxManager::AJAX_SCOPE_DEFAULT,
            AjaxManager::AJAX_ATTR_GROUP => $group,
        ];

        if (null !== $action) {
            $arrParams[AjaxManager::AJAX_ATTR_ACT] = $action;
        }

        $arrConfig = $GLOBALS['AJAX'][$group]['actions'][$action] ?? null;

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if (!$request) {
            return $arrParams;
        }

        if ($arrConfig && ($arrConfig['csrf_protection'] ?? false)) {
            $strToken = $request->query->get(AjaxManager::AJAX_ATTR_TOKEN);

            // create a new token for each action
            if (!$strToken || ($strToken && !System::getContainer()->get('huh.ajax.token')->validate($strToken))) {
                $arrParams[AjaxManager::AJAX_ATTR_TOKEN] = System::getContainer()->get('huh.ajax.token')->create();
            }
        }

        return $arrParams;
    }

    public function call($objContext)
    {
        $objItem = null;

        if (null === $objContext) {
            System::getContainer()->get(AjaxManager::class)->sendResponseError('Bad Request, context not set.');
            throw new \Exception('Bad Request, context not set.');
        }

        if (!method_exists($objContext, $this->strAction)) {
            System::getContainer()->get(AjaxManager::class)->sendResponseError('Bad Request, ajax method does not exist within context.');
            throw new BadMethodCallException('Bad Request, ajax method does not exist within context.');
        }

        $reflection = new \ReflectionMethod($objContext, $this->strAction);

        if (!$reflection->isPublic()) {
            System::getContainer()->get(AjaxManager::class)->sendResponseError('Bad Request, the called method is not public.');
            throw new BadMethodCallException('Bad Request, the called method is not public.');
        }

        return call_user_func_array([$objContext, $this->strAction], $this->getArguments());
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        $arrArgumentValues = [];
        $arrArguments = $this->arrAttributes['arguments'] ?? [];
        $arrOptional = $this->arrAttributes['optional'] ?? [];

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if (!$request) {
            return $arrArgumentValues;
        }

        $arrCurrentArguments = $request->isMethod(Request::METHOD_POST) ? $request->request->all() : $request->query->all();

        foreach ($arrArguments as $argument) {
            if (is_array($argument) || is_bool($argument)) {
                $arrArgumentValues[] = $argument;
                continue;
            }

            if (count(preg_grep('/' . $argument . '/i', $arrOptional)) < 1
                && count(preg_grep('/' . $argument . '/i', array_keys($arrCurrentArguments))) < 1) {
                System::getContainer()->get(AjaxManager::class)->sendResponseError('Bad Request, missing argument ' . $argument);
                throw new AjaxExitException('Bad Request, missing argument ' . $argument);
            }

            $varValue = $request->isMethod(Request::METHOD_POST) ? $request->request->get($argument) : $request->query->get($argument);

            if ('true' === $varValue || 'false' === $varValue) {
                $varValue = filter_var($varValue, FILTER_VALIDATE_BOOLEAN);
            }

            $arrArgumentValues[] = $varValue;
        }

        return $arrArgumentValues;
    }
}
