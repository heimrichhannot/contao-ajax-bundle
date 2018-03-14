<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Manager;

use Contao\System;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

class AjaxTokenManager
{
    /**
     * Constants.
     */
    const SESSION_KEY = 'AJAX_TOKENS';

    /**
     * Tokens.
     *
     * @var array
     */
    protected $tokens;

    /**
     * Current session object.
     *
     * @var Session
     */
    protected $session;

    /**
     * Load the token or generate a new one.
     */
    public function __construct()
    {
        $this->session = System::getContainer()->get('huh.request')->getSession();

        if (!System::getContainer()->get('huh.request')->hasSession()) {
            $this->session = new Session(new PhpBridgeSessionStorage());
            $this->session->start();
        }

        $this->tokens = $this->session->get(static::SESSION_KEY);

        // Generate a new token if none is available
        if (empty($this->tokens) || !is_array($this->tokens)) {
            $this->tokens[] = md5(uniqid(mt_rand(), true));
            $this->session->set(static::SESSION_KEY, $this->tokens);
        }
    }

    /**
     * Return the tokens.
     *
     * @return array The request token
     */
    public function get()
    {
        return $this->tokens;
    }

    /**
     * Remove a used token.
     *
     * @param $token
     */
    public function remove(string $token): void
    {
        System::getContainer()->get('huh.utils.array')->removeValue($token, $this->tokens);

        $this->session->set(static::SESSION_KEY, $this->tokens);
    }

    /**
     * Create a new token.
     *
     * @return string The created request token
     */
    public function create()
    {
        $strToken = md5(uniqid(mt_rand(), true));
        $this->tokens[] = $strToken;

        $this->session->set(static::SESSION_KEY, $this->tokens);

        return $strToken;
    }

    /**
     * Return the valid active token.
     *
     * @return mixed|null the active token if valid, otherwise null
     */
    public function getActiveToken()
    {
        $strToken = System::getContainer()->get('huh.request')->getGet(AjaxManager::AJAX_ATTR_TOKEN);

        if ($strToken && $this->validate($strToken)) {
            return $strToken;
        }

        return null;
    }

    /**
     * Validate a token.
     *
     * @param string $token The ajax token
     *
     * @return bool True if the token matches the stored one
     */
    public function validate(string $token): bool
    {
        // Validate the token
        if ('' !== $token && in_array($token, $this->tokens, true)) {
            return true;
        }

        // Check against the whitelist (thanks to Tristan Lins) (see #3164)
        if (\Config::get('requestTokenWhitelist') && $_SERVER['REMOTE_ADDR']) {
            $strHostname = @gethostbyaddr($_SERVER['REMOTE_ADDR']);

            if ($strHostname) {
                foreach (\Config::get('requestTokenWhitelist') as $strDomain) {
                    if ($strDomain === $strHostname || preg_match('/\.'.preg_quote($strDomain, '/').'$/', $strHostname)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
