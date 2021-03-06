<?php declare(strict_types=1);

namespace Test;

use BeBound\SDK\Configuration;
use BeBound\SDK\Webhook\BaseWebhook;
use BeBound\SDK\Webhook\WebhookFailure;
use BeBound\SDK\Webhook\WebhookRequest;
use PHPUnit\Framework\TestCase;

abstract class WebhookBaseTest extends TestCase
{
    public const BEAPP_NAME = 'beappName';
    public const BEAPP_ID = 13;
    public const BEAPP_VERSION = 2;
    public const BEAPP_SECRET = 'Sup3rS3cr3tch41n';
    public const OPERATION_NAME = 'myOperation';
    public const USER_ID = 'uuid';

    public function provideRequest(): array
    {
        $handlerOK = function (WebhookRequest $req) {
            return ['operationName' => $req->getOperationName()];
        };

        $handlerBug = function (WebhookRequest $req) {
            throw new \RuntimeException('Oops in ' . $req->getOperationName());
        };

        return [
            'Success' => [
                BaseWebhook::HTTP_CODE_OK,
                ['params' => ['operationName' => self::OPERATION_NAME]],
                self::OPERATION_NAME,
                $handlerOK,
                false,
            ],
            'Operation not found' => [
                WebhookFailure::HTTP_CODE_OPERATION_NOT_FOUND,
                ['error' => WebhookFailure::BB_ERROR_METHOD_NOT_FOUND],
                'otherOperation',
                $handlerOK,
                false,
            ],
            'Bugged handler in debug' => [
                WebhookFailure::HTTP_CODE_INTERNAL_ERROR,
                ['error' => WebhookFailure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR],
                self::OPERATION_NAME,
                $handlerBug,
                true,
            ],
            'Bugged handler in production' => [
                WebhookFailure::HTTP_CODE_INTERNAL_ERROR,
                ['error' => WebhookFailure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR],
                self::OPERATION_NAME,
                $handlerBug,
                false,
            ],
        ];
    }

    public function provideWrongBeapp(): array
    {
        return [
            'Wrong BeApp Name' => [
                'anotherName',
                self::BEAPP_VERSION,
                self::BEAPP_VERSION,
                self::BEAPP_SECRET,
                WebhookFailure::HTTP_CODE_WRONG_BEAPP,
                ['error' => WebhookFailure::BB_ERROR_REQUEST_REJECTED]
            ],
            'Wrong BeApp ID' => [
                self::BEAPP_NAME,
                42,
                self::BEAPP_VERSION,
                self::BEAPP_SECRET,
                WebhookFailure::HTTP_CODE_WRONG_BEAPP,
                ['error' => WebhookFailure::BB_ERROR_REQUEST_REJECTED]
            ],
            'Wrong BeApp Version' => [
                self::BEAPP_NAME,
                self::BEAPP_ID,
                1,
                self::BEAPP_SECRET,
                WebhookFailure::HTTP_CODE_WRONG_BEAPP,
                ['error' => WebhookFailure::BB_ERROR_REQUEST_REJECTED]
            ],
        ];
    }

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

    /**
     * @return bool|resource
     */
    protected function createRequestStream()
    {
        $stream = fopen('php://temp', 'wb+');
        fwrite($stream, $this->createRequestData());
        rewind($stream);

        return $stream;
    }
}
