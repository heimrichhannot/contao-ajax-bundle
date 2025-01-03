<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Response;

use Contao\System;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;
use HeimrichHannot\AjaxBundle\Manager\AjaxManager;
use JsonSerializable;
use ReturnTypeWillChange;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Response extends JsonResponse implements JsonSerializable
{
    protected ?ResponseData $result = null;

    protected mixed $message;

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
    public function getResult(): ?ResponseData
    {
        return $this->result ?? new ResponseData();
    }

    /**
     * @param ResponseData $result
     */
    public function setResult(ResponseData $result): void
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getMessage(): mixed
    {
        return $this->message;
    }

    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return array
     */
    #[ReturnTypeWillChange] public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param bool $close
     */
    public function setCloseModal(bool $close = false): void
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
    public function setUrl(string $url): void
    {
        $objResult = $this->getResult();
        $arrData = $objResult->getData();
        $arrData['url'] = $url;
        $objResult->setData($arrData);
        $this->setResult($objResult);
    }

    /**
     * @return stdClass
     */
    public function getOutputData(): stdClass
    {
        $objOutput = new stdClass();
        $objOutput->result = $this->result;
        $objOutput->message = $this->message;
        $objOutput->token = $this->token;

        return $objOutput;
    }

    /**
     * Output the response and clean output buffer.
     *
     * @throws AjaxExitException
     */
    public function output(): void
    {
        // The difference between them is ob_clean wipes the buffer then continues buffering,
        // whereas ob_end_clean wipes it, then stops buffering.
        if (!defined('UNIT_TESTING')) {
            ob_end_clean();
        }

        $strBuffer = json_encode($this->getOutputData());

        $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
        $strBuffer = $insertTagParser->replace($strBuffer);

        $this->setJson($strBuffer);

        $this->send();

        // do not display errors in ajax request, as the generated json will no longer be valid
        // error messages my occur, due to exit and \FrontendUser::destruct does no longer have a valid \Database instance
        ini_set('display_errors', 0);

        System::getContainer()->get(AjaxManager::class)->exit();
    }

    /**
     * @throws AjaxExitException
     *
     * @return JsonResponse
     */
    public function send(): static
    {
        if (defined('UNIT_TESTING')) {
            throw new AjaxExitException(json_encode($this), AjaxExitException::CODE_NORMAL_EXIT);
        }

        return parent::send();
    }
}
