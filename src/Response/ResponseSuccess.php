<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Response;

class ResponseSuccess extends Response
{
    public function __construct($message = '')
    {
        parent::__construct($message);
        $this->setStatusCode(Response::HTTP_OK);
    }
}
