<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Response;

use Contao\System;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;

abstract class Response extends \Symfony\Component\HttpFoundation\JsonResponse implements \JsonSerializable
{
    protected $result;

    protected $message;

    protected $token;

    public function __construct($message = '')
    {
        parent::__construct($message);
        $this->message = $message;
        $this->token = System::getContainer()->get('huh.ajax.token')->getActiveToken();

        // create a new token for each response
        if (null !== $this->token) {
            $this->token = System::getContainer()->get('huh.ajax.token')->create();
        }
    }

    /**
     * @return ResponseData
     */
    public function getResult()
    {
        return null === $this->result ? new ResponseData() : $this->result;
    }

    /**
     * @param ResponseData $result
     */
    public function setResult(ResponseData $result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @param bool $close
     */
    public function setCloseModal(bool $close = false)
    {
        $objResult = $this->getResult();
        $arrData = $objResult->getData();
        $arrData['closeModal'] = $close;
        $objResult->setData($arrData);
        $this->setResult($objResult);
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $objResult = $this->getResult();
        $arrData = $objResult->getData();
        $arrData['url'] = $url;
        $objResult->setData($arrData);
        $this->setResult($objResult);
    }

    /**
     * @return \stdClass
     */
    public function getOutputData()
    {
        $objOutput = new \stdClass();
        $objOutput->result = $this->result;
        $objOutput->message = $this->message;
        $objOutput->token = $this->token;

        return $objOutput;
    }

    /**
     * Output the response and clean output buffer.
     */
    public function output()
    {
        // The difference between them is ob_clean wipes the buffer then continues buffering,
        // whereas ob_end_clean wipes it, then stops buffering.
        if (!defined('UNIT_TESTING')) {
            ob_end_clean();
        }

        $strBuffer = json_encode($this->getOutputData());

        $strBuffer = \Controller::replaceInsertTags($strBuffer, false); // do not cache inserttags

        $this->setJson($strBuffer);

        $this->send();

        // do not display errors in ajax request, as the generated json will no longer be valid
        // error messages my occur, due to exit and \FrontendUser::destruct does no longer have a valid \Database instance
        ini_set('display_errors', 0);

        throw new AjaxExitException('exit');
    }

    /**
     * @throws AjaxExitException
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function send()
    {
        if (defined('UNIT_TESTING')) {
            throw new AjaxExitException(json_encode($this), AjaxExitException::CODE_NORMAL_EXIT);
        }

        return parent::send();
    }
}
