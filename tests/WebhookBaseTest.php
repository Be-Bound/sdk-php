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
    public const USER_ID = 'uuid';

    protected function createRequestData(
        array $params = [],
        ?int $moduleId = null,
        ?string $moduleName = null,
        ?int $moduleVersion = null,
        ?string $operationName = null,
        ?string $userID = null
    ): string {
        $moduleId = $moduleId ?? self::BEAPP_ID;
        $moduleName = $moduleName ?? self::BEAPP_NAME;
        $moduleVersion = $moduleVersion ?? self::BEAPP_VERSION;
        $operationName = $operationName ?? self::OPERATION_NAME;
        $userID = $userID ?? self::USER_ID;

        $data = [
            'transport' => 'web',
            'userId' => $userID,
            'moduleId' => $moduleId,
            'moduleName' => $moduleName,
            'moduleVersion' => $moduleVersion,
            'operation' => $operationName,
            'params' => $params,
        ];

        return \json_encode($data);
    }

    protected function createBasicAuth(string $user = 'user', string $password = 'password'): string
    {
        return 'Basic ' . base64_encode(sprintf('%s:%s', $user, $password));
    }

    protected function instantiateConfiguration(
        bool $debugMode = false,
        string $beappName = self::BEAPP_NAME,
        int $beappId = self::BEAPP_ID,
        int $beappVersion = self::BEAPP_VERSION,
        string $beappSecret = self::BEAPP_SECRET
    ): Configuration {
        $configuration = new Configuration(
            $beappName,
            $beappId,
            $beappVersion,
            $beappSecret
        );

        if ($debugMode) {
            $configuration->enableDebug();
        }

        return $configuration;
    }
}
