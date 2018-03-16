<?php declare(strict_types=1);

namespace Test\Unit;

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
     * @dataProvider provideWrongBeapp
     * @param string $beappName
     * @param int $beappId
     * @param int $beappVersion
     * @param string $beappSecret
     * @throws \Throwable
     */
    public function webhookHandlerShouldRejectWrongBeapp(
        string $beappName,
        int $beappId,
        int $beappVersion,
        string $beappSecret
    ): void {
        $configuration = $this->instantiateConfiguration(
            false,
            $beappName,
            $beappId,
            $beappVersion,
            $beappSecret
        );

        $stream = $this->prophesize(StreamInterface::class);
        $stream->getContents()->willReturn($this->createRequestData());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getBody()->willReturn($stream->reveal());
        $request->getHeaderLine('Authorization')->willReturn($this->createBasicAuth());
        $request = $request->reveal();

        $response = $this->prophesize(ResponseInterface::class);
        $subject = new WebhookMiddleware($configuration, $response->reveal());

        $fallbackResponse = $this->prophesize(ResponseInterface::class);
        $fallback = $this->prophesize(RequestHandlerInterface::class);
        $fallback->handle($request)->willReturn($fallbackResponse->reveal());

        $result = $subject->process($request, $fallback->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
