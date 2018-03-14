<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Response;

use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Response\ResponseData;

class ResponseDataTest extends ContaoTestCase
{
    public function testSetGetData()
    {
        $response = new ResponseData();
        $response->setData(['data']);
        $this->assertSame(['data'], $response->getData());
    }

    public function testSetGetHtml()
    {
        $response = new ResponseData();
        $response->setHtml('html');
        $this->assertSame('html', $response->getHtml());
    }

    public function testJsonSerialize()
    {
        $response = new ResponseData('html', ['data']);
        $this->assertSame(['data' => ['data'], 'html' => 'html'], $response->jsonSerialize());
    }
}
