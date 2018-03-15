<?php declare(strict_types=1);

namespace Test\Unit;

use BeBound\SDK\Configuration;
use Psr\Log\NullLogger;
use Test\WebhookBaseTest;

class ConfigurationTest extends WebhookBaseTest
{
    /**
     * @test
     */
    public function configurationShouldAlwaysProvideAValidLogger(): void
    {
        $subject = $this->getConfiguration();

        $this->assertInstanceOf(NullLogger::class, $subject->getLogger());
    }

    /**
     * @test
     */
    public function configurationValueObjectIsImmutable(): void
    {
        $subject = $this->getConfiguration();

        $this->assertEquals(self::BEAPP_NAME, $subject->getBeappName());
        $this->assertEquals(self::BEAPP_ID, $subject->getBeappId());
        $this->assertEquals(self::BEAPP_VERSION, $subject->getBeappVersion());
        $this->assertEquals(self::BEAPP_SECRET, $subject->getBeappSecret());
    }

    /**
     * @test
     */
    public function configurationDebugModeIsSettable(): void
    {
        $subject = $this->getConfiguration();

        $this->assertFalse($subject->isDebug());

        $subject->enableDebug();
        $this->assertTrue($subject->isDebug());

        $subject->disableDebug();
        $this->assertFalse($subject->isDebug());
    }

    private function getConfiguration(): Configuration
    {
        return new Configuration(self::BEAPP_NAME, self::BEAPP_ID, self::BEAPP_VERSION, self::BEAPP_SECRET);
    }
}
