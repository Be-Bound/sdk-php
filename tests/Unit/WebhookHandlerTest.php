<?php declare(strict_types=1);

namespace Test\Unit;

use BeBound\SDK\Webhook\Failure;
use BeBound\SDK\WebhookHandler;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Test\WebhookBaseTest;

class WebhookHandlerTest extends WebhookBaseTest
{
    /**
     * @test
     * @dataProvider provideWrongBeapp
     * @param string $beappName
     * @param int $beappId
     * @param int $beappVersion
     * @param string $beappSecret
     * @param int $httpCode
     * @param array $responsePayload
     * @throws \Throwable
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
            false,
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
        return array_merge(
            parent::provideWrongBeapp(),
            [
                'Wrong BeApp Secret' => [
                    self::BEAPP_NAME,
                    self::BEAPP_ID,
                    self::BEAPP_VERSION,
                    'notTheGoodSecret',
                    Failure::HTTP_CODE_WRONG_AUTHORIZATION,
                    ['error' => Failure::BB_ERROR_AUTHORIZATION]
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function webhookShouldRejectWrongHandler(): void
    {
        $this->expectException(\TypeError::class);

        $configuration = $this->instantiateConfiguration();
        $response = $this->prophesize(ResponseInterface::class);

        $subject = new WebhookHandler($configuration, $response->reveal());
        $subject->add(self::OPERATION_NAME, 'notACallable');
    }
}
