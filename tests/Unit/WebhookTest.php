<?php declare(strict_types=1);

namespace Test\Unit;

use BeBound\SDK\Webhook;
use Test\WebhookBaseTest;

class WebhookTest extends WebhookBaseTest
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
    public function webhookMiddlewareShouldRejectWrongBeapp(
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

        $subject = new Webhook($configuration);

        $response = $subject->run();

        $this->assertEmpty($response);
    }
}
