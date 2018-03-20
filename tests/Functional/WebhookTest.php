<?php declare(strict_types=1);

namespace Test\Functional;

use BeBound\SDK\Webhook;
use Test\WebhookBaseTest;

class WebhookTest extends WebhookBaseTest
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
    public function webhookShouldRunRequest(
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

        $subject = new Webhook($configuration);
        $subject->add($operationName, $handler);

        $data = \json_decode($this->createRequestData(), true);

        $request = new Webhook\WebhookRequest(
            self::BEAPP_NAME,
            self::BEAPP_ID,
            self::BEAPP_VERSION,
            self::BEAPP_SECRET,
            self::USER_ID,
            Webhook\WebhookRequest::TRANSPORT_TYPE_WEB,
            $data['operation'],
            $data['params']
        );

        $result = $subject->run($request, true);

        $this->assertJson($result);
    }
}
