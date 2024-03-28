<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Response;

use JsonSerializable;

class ResponseData implements JsonSerializable
{
    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var string
     */
    protected mixed $html;

    public function __construct($html = '', $data = [])
    {
        $this->data = $data;
        $this->html = $html;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getHtml(): mixed
    {
        return $this->html;
    }

    /**
     * @param mixed $html
     */
    public function setHtml(mixed $html): void
    {
        $this->html = $html;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}