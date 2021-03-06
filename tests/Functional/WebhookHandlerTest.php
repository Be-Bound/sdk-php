<?php declare(strict_types=1);

namespace Test\Functional;

use BeBound\SDK\WebhookHandler;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Test\WebhookBaseTest;

class WebhookHandlerTest extends WebhookBaseTest
{
    /**
     * @test
     * @dataProvider provideRequest
     * @param int $expectedResponseCode
     * @param array $expectedResponsePayload
     * @param string $operationName
     * @param callable $handler
     * @param bool $debugMode
     * @throws \Throwable
     */
    public function webhookShouldHandleRequest(
        int $expectedResponseCode,
        array $expectedResponsePayload,
        string $operationName,
        callable $handler,
        bool $debugMode
    ): void {
        if ($debugMode) {
            $this->expectException(\Throwable::class);
        }

        $configuration = $this->instantiateConfiguration($debugMode);

        $streamResponse = $this->prophesize(StreamInterface::class);
        if (!$debugMode) {
            $streamResponse->write(Argument::exact(\json_encode($expectedResponsePayload)))->shouldBeCalled();
        }
        $response = $this->prophesize(ResponseInterface::class);
        $response->withStatus(Argument::exact($expectedResponseCode))->willReturn($response);
        $response->getBody()->willReturn($streamResponse->reveal());

        $streamRequest = $this->prophesize(StreamInterface::class);
        $streamRequest->getContents()->willReturn($this->createRequestData());
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getBody()->willReturn($streamRequest->reveal());
        $request->getHeaderLine('Authorization')->willReturn($this->createBasicAuth(
            self::BEAPP_NAME,
            self::BEAPP_SECRET
        ));

        $subject = new WebhookHandler($configuration, $response->reveal());
        $subject->add($operationName, $handler);

        $result = $subject->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
