<?php declare(strict_types=1);

namespace Test\Functional;

use BeBound\SDK\Webhook\Failure;
use BeBound\SDK\Webhook\Request;
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
     * @param string $operationName
     * @param callable $handler
     * @param array $responsePayload
     * @throws \Throwable
     */
    public function webhookShouldHandleRequest(
        int $expectedResponseCode,
        string $operationName,
        callable $handler,
        array $responsePayload
    ): void {
        $configuration = $this->instantiateConfiguration();

        $streamResponse = $this->prophesize(StreamInterface::class);
        $streamResponse->write(Argument::exact(\json_encode($responsePayload)))->shouldBeCalled();
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

    public function provideRequest(): array
    {
        $handlerOK = function (Request $req) {
            return ['operationName' => $req->getOperationName()];
        };
        return [
            'Success' => [
                WebhookHandler::HTTP_CODE_OK,
                self::OPERATION_NAME,
                $handlerOK,
                ['params' => ['operationName' => self::OPERATION_NAME]],
            ],
            'Operation not found' => [
                Failure::HTTP_CODE_OPERATION_NOT_FOUND,
                'otherOperation',
                $handlerOK,
                ['error' => Failure::BB_ERROR_METHOD_NOT_FOUND],
            ],
        ];
    }
}
