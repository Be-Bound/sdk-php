<?php declare(strict_types=1);

namespace Test;

use BeBound\SDK\Configuration;
use PHPUnit\Framework\TestCase;

abstract class WebhookBaseTest extends TestCase
{
    public const BEAPP_NAME = 'beappName';
    public const BEAPP_ID = 13;
    public const BEAPP_VERSION = 2;
    public const BEAPP_SECRET = 'Sup3rS3cr3tch41n';
    public const OPERATION_NAME = 'myOperation';

    protected function createRequestData(
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

    protected function createBasicAuth(string $user = 'user', string $password = 'password'): string
    {
        return 'Basic ' . base64_encode(sprintf('%s:%s', $user, $password));
    }

    protected function instantiateConfiguration(
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
