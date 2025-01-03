<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Manager;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\System;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;
use HeimrichHannot\AjaxBundle\Response\Response;
use HeimrichHannot\AjaxBundle\Response\ResponseError;
use Symfony\Component\HttpFoundation\RequestStack;

class AjaxManager
{
    public const AJAX_ATTR_SCOPE = 'as';
    public const AJAX_ATTR_ACT = 'aa';
    public const AJAX_ATTR_GROUP = 'ag';
    public const AJAX_ATTR_TYPE = 'at';
    public const AJAX_ATTR_AJAXID = 'aid';
    public const AJAX_ATTR_TOKEN = 'ato';

    public const AJAX_ATTRIBUTES = [
        self::AJAX_ATTR_SCOPE,
        self::AJAX_ATTR_ACT,
        self::AJAX_ATTR_GROUP,
        self::AJAX_ATTR_TYPE,
        self::AJAX_ATTR_AJAXID,
        self::AJAX_ATTR_TOKEN,
    ];

    public const AJAX_SCOPE_DEFAULT = 'ajax';
    public const AJAX_TYPE_MODULE = 'module';

    public const AJAX_ERROR_INVALID_GROUP = 1;
    public const AJAX_ERROR_NO_AVAILABLE_ACTIONS = 2;
    public const AJAX_ERROR_INVALID_ACTION = 3;
    public const AJAX_ERROR_INVALID_TOKEN = 4;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
    ) {
    }

    /**
     * Determine if the current ajax request is group related.
     *
     * @param string $groupRequested The ajax group
     *
     * @return bool|null True / False if group from request match, otherwise null (no ajax request)
     */
    public function isRelated(string $groupRequested): ?bool
    {
        if ((null !== $this->requestStack->getCurrentRequest())
            && $this->requestStack->getCurrentRequest()->isXmlHttpRequest()) {
            return null !== $this->getActiveGroup($groupRequested);
        }

        return null;
    }

    /**
     * Trigger a valid ajax action.
     *
     * @throws AjaxExitException
     */
    public function runActiveAction(string $group, string $action, $objContext): void
    {
        if ((null !== $this->requestStack->getCurrentRequest())
            && $this->requestStack->getCurrentRequest()->isXmlHttpRequest()) {
            // Add custom logic via hook
            $hooks = $GLOBALS['TL_HOOKS']['beforeAjaxAction'] ?? null;
            if (is_array($hooks)) {
                foreach ($hooks as $callback) {
                    if (is_array($callback)) {
                        $callbackObj = System::importStatic($callback[0]);
                        $callbackObj->{$callback[1]}($group, $action, $objContext);
                    } elseif (is_callable($callback)) {
                        $callback($group, $action, $objContext);
                    }
                }
            }

            /** @var AjaxActionManager */
            $objAction = $this->getActiveAction($group, $action);

            $error = match ($objAction) {
                static::AJAX_ERROR_INVALID_GROUP => 'Invalid ajax group.',
                static::AJAX_ERROR_NO_AVAILABLE_ACTIONS => 'No available ajax actions within given group.',
                static::AJAX_ERROR_INVALID_ACTION => 'Invalid ajax act.',
                static::AJAX_ERROR_INVALID_TOKEN => 'Invalid ajax token.',
                default => null,
            };

            if (null !== $error) {
                $this->sendResponseError($error);
                $this->exit();
            }

            if (null !== $objAction) {
                $objResponse = $objAction->call($objContext);

                /* @var Response $objResponse */
                if ($objResponse instanceof Response) {
                    // remove used ajax tokens
                    $strToken = System::getContainer()->get('huh.ajax.token')->getActiveToken();
                    if (null !== $strToken) {
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
     * @return string|null The name of the active group, otherwise null
     */
    public function getActiveGroup(string $strGroupRequested): ?string
    {
        $strScope = $this->requestStack->getCurrentRequest()->query->get(static::AJAX_ATTR_SCOPE);
        $strGroup = $this->requestStack->getCurrentRequest()->query->get(static::AJAX_ATTR_GROUP);

        if ($strScope !== static::AJAX_SCOPE_DEFAULT) {
            return null;
        }

        if (!$strGroup || $strGroupRequested !== $strGroup) {
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
     * @return AjaxActionManager|int|null A valid AjaxAction | null if the action is not a registered ajax action
     */
    public function getActiveAction(string $groupRequested, string $actionRequested): AjaxActionManager|int|null
    {
        $strAct = $this->requestStack->getCurrentRequest()->query->get(static::AJAX_ATTR_ACT);
        $strToken = $this->requestStack->getCurrentRequest()->query->get(static::AJAX_ATTR_TOKEN);

        if (!$strAct) {
            return null;
        }

        $strGroup = $this->getActiveGroup($groupRequested);

        if (null === $strGroup) {
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
        if (isset($arrAttributes['csrf_protection'])
            && $arrAttributes['csrf_protection']
            && (!$strToken || !System::getContainer()->get('huh.ajax.token')->validate($strToken))) {
            return static::AJAX_ERROR_INVALID_TOKEN;
        }

        return new AjaxActionManager($strGroup, $strAct, $arrAttributes, $strToken);
    }

    /**
     * Set new request token and set expired state within $_POST as param REQUEST_TOKEN_EXPIRED.
     */
    public function setRequestTokenExpired(): void
    {
        $token = $this->csrfTokenManager->getDefaultTokenValue();
        $_POST['REQUEST_TOKEN_EXPIRED'] = true;
        $_POST['REQUEST_TOKEN'] = $token;

        $this->requestStack->getCurrentRequest()->request->set('REQUEST_TOKEN', $token);
        $this->requestStack->getCurrentRequest()->request->set('REQUEST_TOKEN_EXPIRED', true);
    }

    /**
     * Return true if the request token has expired in between.
     */
    public function isRequestTokenExpired(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->scopeMatcher->isFrontendRequest($request)
            && $request->isXmlHttpRequest()
            && Input::post('REQUEST_TOKEN_EXPIRED');
    }

    /**
     * @throws AjaxExitException
     */
    public function sendResponseError(string $message): void
    {
        $objResponse = new ResponseError($message);
        $objResponse->send();
    }

    /**
     * exit function for testing.
     */
    public function exit(): never
    {
        exit;
    }
}
