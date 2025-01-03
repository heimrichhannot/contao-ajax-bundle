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
     * @param mixed[] $data
     * @param string $html
     */
    public function __construct(protected mixed $html = '', protected array $data = [])
    {
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