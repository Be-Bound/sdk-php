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

    public function provideRequest(): array
    {
        $handlerOK = function (Request $req) {
            return ['operationName' => $req->getOperationName()];
        };

        $handlerBug = function (Request $req) {
            throw new \RuntimeException('Oops');
            return ['operationName' => $req->getOperationName()];
        };

        return [
            'Success' => [
                WebhookHandler::HTTP_CODE_OK,
                ['params' => ['operationName' => self::OPERATION_NAME]],
                self::OPERATION_NAME,
                $handlerOK,
                false,
            ],
            'Operation not found' => [
                Failure::HTTP_CODE_OPERATION_NOT_FOUND,
                ['error' => Failure::BB_ERROR_METHOD_NOT_FOUND],
                'otherOperation',
                $handlerOK,
                false,
            ],
            'Bugged handler in debug' => [
                Failure::HTTP_CODE_INTERNAL_ERROR,
                ['error' => Failure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR],
                self::OPERATION_NAME,
                $handlerBug,
                true,
            ],
            'Bugged handler in production' => [
                Failure::HTTP_CODE_INTERNAL_ERROR,
                ['error' => Failure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR],
                self::OPERATION_NAME,
                $handlerBug,
                false,
            ],
        ];
    }
}
