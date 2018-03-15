<?php declare(strict_types=1);

namespace BeBound\SDK;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Configuration
{
    private $beappName;
    private $beappId;
    private $beappVersion;
    private $beappSecret;
    private $debug = false;

    private $logger;

    public function __construct(
        string $beappName,
        int $beappId,
        int $beappVersion,
        string $beappSecret,
        ?LoggerInterface $logger = null
    ) {
        $this->beappName = $beappName;
        $this->beappId = $beappId;
        $this->beappVersion = $beappVersion;
        $this->beappSecret = $beappSecret;

        $this->logger = $logger ?? new NullLogger();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function enableDebug(): self
    {
        $this->debug = true;

        return $this;
    }

    public function disableDebug(): self
    {
        $this->debug = false;

        return $this;
    }
}
