<?php declare(strict_types=1);

namespace Test\Unit;

use BeBound\SDK\Webhook\Request;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Test\WebhookBaseTest;

class RequestTest extends WebhookBaseTest
{
    /**
     * @test
     */
    public function canBeCreatedFromPsr7ServerRequest(): void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->getContents()->willReturn($this->createRequestData());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getBody()->willReturn($stream->reveal());
        $request->getHeaderLine('Authorization')->willReturn($this->createBasicAuth());

        $subject = Request::fromPSR7Request($request->reveal());

        $this->assertEquals(self::OPERATION_NAME, $subject->getOperationName());
        $this->assertEquals(Request::TRANSPORT_TYPE_WEB, $subject->getTransportType());
        $this->assertEquals(self::USER_ID, $subject->getUserID());
        $this->assertEquals([], $subject->getOperationParams());
    }

    /**
     * @test
     */
    public function canBeCreatedFromEnvironment(): void
    {
        $stream = $this->createRequestStream();

        $subject = Request::fromEnvironment($stream, self::BEAPP_SECRET);

        $this->assertInstanceOf(Request::class, $subject);
        $this->assertEquals(self::OPERATION_NAME, $subject->getOperationName());
    }
}
