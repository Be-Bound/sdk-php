<?php declare(strict_types=1);

namespace Test\Functional;

use BeBound\SDK\WebhookMiddleware;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Test\WebhookBaseTest;

class WebhookMiddlewareTest extends WebhookBaseTest
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
    public function webhookShouldProcessRequest(
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

        $subject = new WebhookMiddleware($configuration, $response->reveal());
        $subject->add($operationName, $handler);

        $fallback = $this->prophesize(RequestHandlerInterface::class);

        $result = $subject->process($request->reveal(), $fallback->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
