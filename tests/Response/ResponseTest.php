<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AjaxBundle\Tests\Response;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\AjaxBundle\Exception\AjaxExitException;
use HeimrichHannot\AjaxBundle\Response\Response;
use HeimrichHannot\AjaxBundle\Response\ResponseData;
use HeimrichHannot\AjaxBundle\Response\ResponseSuccess;

class ResponseTest extends ContaoTestCase
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

    public function testGetterAndSetter()
    {
        $response = new ResponseSuccess('test');
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $response->setMessage('message');
        $this->assertSame('message', $response->getMessage());

        $response->setResult(new ResponseData('', ['data']));
        $this->assertInstanceOf(ResponseData::class, $response->getResult());
        $this->assertSame(['data'], $response->getResult()->getData());

        $this->assertSame('token', $response->getToken());

        // set close Modal
        $response->setCloseModal();
        $this->assertArrayHasKey('closeModal', $response->getResult()->getData());

        $result = $response->getOutputData();
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('message', $result->message);
        $this->assertSame('token', $result->token);

        // set url
        $response->setUrl('url');
        $this->assertArrayHasKey('url', $response->getResult()->getData());
    }

    public function testSend()
    {
        if (!defined('UNIT_TESTING')) {
            define('UNIT_TESTING', true);
        }
        $response = new ResponseSuccess('test');

        try {
            $response->send();
        } catch (AjaxExitException $exception) {
            $this->assertSame('{"result":null,"message":"test","token":"token","data":"\"test\"","callback":null,"encodingOptions":15,"headers":{},"content":"\"test\"","version":"1.0","statusCode":200,"statusText":"OK","charset":null}', $exception->getMessage());
        }
    }

    /**
     * @covers \Response::exit()
     */
    public function testExit()
    {
        $response = $this->getMockBuilder(ResponseSuccess::class)->setMethods(['exit'])->getMock();
        $response->method('exit')->willThrowException(new AjaxExitException('exit'));

        try {
            $response->exit();
        } catch (AjaxExitException $exception) {
            $this->assertSame('exit', $exception->getMessage());
        }
    }
}
