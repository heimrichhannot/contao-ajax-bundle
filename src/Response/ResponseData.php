<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Response;

class ResponseData implements \JsonSerializable
{
    /**
     * @param mixed[] $data
     * @param string  $html
     */
    public function __construct(
        protected mixed $html = '',
        protected array $data = [],
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getHtml(): mixed
    {
        return $this->html;
    }

    public function setHtml(mixed $html): void
    {
        $this->html = $html;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
