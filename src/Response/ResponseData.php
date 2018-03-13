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
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $html;

    public function __construct($html = '', $data = [])
    {
        $this->data = $data;
        $this->html = $html;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param mixed $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
