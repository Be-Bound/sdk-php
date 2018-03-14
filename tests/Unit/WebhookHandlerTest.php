<?php declare(strict_types=1);

namespace Test\Unit;

use BeBound\SDK\Configuration;
use BeBound\SDK\Webhook\Failure;
use BeBound\SDK\Webhook\Request;
use BeBound\SDK\WebhookHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class WebhookHandlerTest extends TestCase
{
    public const BEAPP_NAME = 'beappName';
    public const BEAPP_ID = 13;
    public const BEAPP_VERSION = 2;
    public const BEAPP_SECRET = 'Sup3rS3cr3tch41n';
    public const OPERATION_NAME = 'myOperation';

    /**
     * @test
     * @dataProvider provideRequest
     * @param int $expectedResponseCode
     * @param string $operationName
     * @param callable $handler
     * @param array $responsePayload
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

    /**
     * @test
     * @dataProvider provideWrongBeapp
     * @param string $beappName
     * @param int $beappId
     * @param int $beappVersion
     * @param string $beappSecret
     * @param int $httpCode
     * @param array $responsePayload
     */
    public function webhookHandlerShouldRejectWrongBeapp(
        string $beappName,
        int $beappId,
        int $beappVersion,
        string $beappSecret,
        int $httpCode,
        array $responsePayload
    ): void {
        $configuration = $this->instantiateConfiguration(
            $beappName,
            $beappId,
            $beappVersion,
            $beappSecret
        );

        $streamResponse = $this->prophesize(StreamInterface::class);
        $streamResponse->write(Argument::exact(\json_encode($responsePayload)))->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->shouldBeCalled()->willReturn($streamResponse->reveal());
        $response->withStatus(Argument::exact($httpCode))->shouldBeCalled()->willReturn($response);

        $stream = $this->prophesize(StreamInterface::class);
        $stream->getContents()->willReturn($this->createRequestData());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getBody()->willReturn($stream->reveal());
        $request->getHeaderLine('Authorization')->willReturn($this->createBasicAuth());

        $subject = new WebhookHandler($configuration, $response->reveal());

        $result = $subject->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function provideWrongBeapp(): array
    {
        return [
            'Wrong BeApp Name' => [
                'anotherName',
                self::BEAPP_VERSION,
                self::BEAPP_VERSION,
                self::BEAPP_SECRET,
                Failure::HTTP_CODE_WRONG_BEAPP,
                ['error' => Failure::BB_ERROR_REQUEST_REJECTED]
            ],
            'Wrong BeApp ID' => [
                self::BEAPP_NAME,
                42,
                self::BEAPP_VERSION,
                self::BEAPP_SECRET,
                Failure::HTTP_CODE_WRONG_BEAPP,
                ['error' => Failure::BB_ERROR_REQUEST_REJECTED]
            ],
            'Wrong BeApp Version' => [
                self::BEAPP_NAME,
                self::BEAPP_ID,
                1,
                self::BEAPP_SECRET,
                Failure::HTTP_CODE_WRONG_BEAPP,
                ['error' => Failure::BB_ERROR_REQUEST_REJECTED]
            ],
            'Wrong BeApp Secret' => [
                self::BEAPP_NAME,
                self::BEAPP_ID,
                self::BEAPP_VERSION,
                'notTheGoodSecret',
                Failure::HTTP_CODE_WRONG_AUTHORIZATION,
                ['error' => Failure::BB_ERROR_AUTHORIZATION]
            ],
        ];
    }

    /**
     * @test
     */
    public function webhookHandlerShouldRejectWrongHandler(): void
    {
        $this->expectException(\TypeError::class);

        $configuration = $this->instantiateConfiguration();
        $response = $this->prophesize(ResponseInterface::class);

        $subject = new WebhookHandler($configuration, $response->reveal());
        $subject->add(self::OPERATION_NAME, 'notACallable');
    }

    private function createRequestData(
        array $params = [],
        ?int $moduleId = null,
        ?string $moduleName = null,
        ?int $moduleVersion = null,
        ?string $operationName = null
    ): string {
        $moduleId = $moduleId ?? self::BEAPP_ID;
        $moduleName = $moduleName ?? self::BEAPP_NAME;
        $moduleVersion = $moduleVersion ?? self::BEAPP_VERSION;
        $operationName = $operationName ?? self::OPERATION_NAME;
        $format = <<<JSON
{
    "transport":"web",
    "userId":"id",
    "moduleId":%s,
    "moduleName":"%s",
    "moduleVersion":%s,
    "operation":"%s",
    "params":%s
}
JSON;
        return sprintf(
            $format,
            $moduleId,
            $moduleName,
            $moduleVersion,
            $operationName,
            json_encode($params)
        );
    }

    private function createBasicAuth(string $user = 'user', string $password = 'password'): string
    {
        return 'Basic ' . base64_encode(sprintf('%s:%s', $user, $password));
    }

    private function instantiateConfiguration(
        ?string $beappName = self::BEAPP_NAME,
        ?int $beappId = self::BEAPP_ID,
        ?int $beappVersion = self::BEAPP_VERSION,
        ?string $beappSecret = self::BEAPP_SECRET
    ): Configuration {
        $configuration = new Configuration(
            $beappName,
            $beappId,
            $beappVersion,
            $beappSecret
        );

        return $configuration;
    }
}
