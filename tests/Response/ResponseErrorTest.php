<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Response;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Response\Response;
use HeimrichHannot\AjaxBundle\Response\ResponseError;

class ResponseErrorTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        $token = $this->mockAdapter(['getActiveToken', 'create']);
        $token->method('getActiveToken')->willReturn('token');
        $token->method('create')->willReturn('token');

        $container = $this->mockContainer();
        $container->set('huh.ajax.token', $token);
        System::setContainer($container);
    }

    public function testResponse()
    {
        $response = new ResponseError('test');
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('test', $response->getMessage());
    }
}
