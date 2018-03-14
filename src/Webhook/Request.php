<?php declare(strict_types=1);

namespace BeBound\SDK\Webhook;

use Psr\Http\Message\RequestInterface;

class Request
{
    private $beappName;
    private $beappId;
    private $beappVersion;
    private $beappSecret;
    private $operationName;

    public function __construct(
        string $beappName,
        int $beappId,
        int $beappVersion,
        string $beappSecret,
        string $operationName
    ) {
        $this->beappName = $beappName;
        $this->beappId = $beappId;
        $this->beappVersion = $beappVersion;
        $this->beappSecret = $beappSecret;
        $this->operationName = $operationName;
    }

    public static function fromPSR7Request(RequestInterface $request): Request
    {
        $secret = '';
        $data = \json_decode($request->getBody()->getContents(), true);

        if (preg_match("/Basic\s+(.*)$/i", $request->getHeaderLine('Authorization'), $matches)) {
            $explodedCredential = explode(':', base64_decode($matches[1]), 2);
            if (\count($explodedCredential) === 2) {
                [, $secret] = $explodedCredential;
            }
        }

        return new self(
            $data['moduleName'],
            $data['moduleId'],
            $data['moduleVersion'],
            $secret,
            $data['operation']
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

    public function getOperationName(): string
    {
        return $this->operationName;
    }
}
