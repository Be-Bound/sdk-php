<?php declare(strict_types=1);

namespace Test\Unit;

use BeBound\SDK\Configuration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConfigurationTest extends TestCase
{
    public const BEAPP_NAME = 'beappName';
    public const BEAPP_ID = 13;
    public const BEAPP_VERSION = 2;
    public const BEAPP_SECRET = 'Sup3rS3cr3tch41n';

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

    private function getConfiguration(): Configuration
    {
        return new Configuration(self::BEAPP_NAME, self::BEAPP_ID, self::BEAPP_VERSION, self::BEAPP_SECRET);
    }
}
