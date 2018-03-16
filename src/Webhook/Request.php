<?php declare(strict_types=1);

namespace BeBound\SDK\Webhook;

use Psr\Http\Message\ServerRequestInterface;

class Request
{
    public const TRANSPORT_TYPE_SMS = 1;
    public const TRANSPORT_TYPE_WEB = 2;

    private const TRANSPORT_TYPES = [
        'sms' => self::TRANSPORT_TYPE_SMS,
        'web' => self::TRANSPORT_TYPE_WEB,
    ];

    private $beappName;
    private $beappId;
    private $beappVersion;
    private $beappSecret;
    private $userID;
    private $transportType;
    private $operationName;
    private $operationParams;

    public function __construct(
        string $beappName,
        int $beappId,
        int $beappVersion,
        string $beappSecret,
        string $userID,
        int $transportType,
        string $operationName,
        array $operationParams
    ) {
        $this->beappName = $beappName;
        $this->beappId = $beappId;
        $this->beappVersion = $beappVersion;
        $this->beappSecret = $beappSecret;
        $this->userID = $userID;
        $this->transportType = $transportType;
        $this->operationName = $operationName;
        $this->operationParams = $operationParams;
    }

    public static function fromPSR7Request(ServerRequestInterface $request): Request
    {
        $data = \json_decode($request->getBody()->getContents(), true);

        $secret = self::parseBasicAuthCredentials($request);

        return new self(
            $data['moduleName'],
            $data['moduleId'],
            $data['moduleVersion'],
            $secret,
            $data['userId'],
            self::TRANSPORT_TYPES[$data['transport']],
            $data['operation'],
            $data['params']
        );
    }

    public function getBeappName(): string
    {
        return $this->beappName;
    }

    public function getBeappId(): int
    {
        return $this->beappId;
    }

    public function getBeappVersion(): int
    {
        return $this->beappVersion;
    }

    public function getBeappSecret(): string
    {
        return $this->beappSecret;
    }

    public function getTransportType(): int
    {
        return $this->transportType;
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }

    public function getUserID(): string
    {
        return $this->userID;
    }

    public function getOperationParams(): array
    {
        return $this->operationParams;
    }

    private static function parseBasicAuthCredentials(ServerRequestInterface $request): string
    {
        $secret = '';
        if (preg_match("/Basic\s+(.*)$/i", $request->getHeaderLine('Authorization'), $matches)) {
            $explodedCredential = explode(':', base64_decode($matches[1]), 2);
            if (\count($explodedCredential) === 2) {
                [, $secret] = $explodedCredential;
            }
        }

        return $secret;
    }
}
