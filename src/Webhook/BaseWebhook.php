<?php declare(strict_types=1);

namespace BeBound\SDK\Webhook;

use BeBound\SDK\Configuration;

abstract class BaseWebhook
{
    protected $configuration;
    protected $logger;
    protected $operations = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = clone $configuration;
        $this->logger = $configuration->getLogger();
    }

    public function add(string $operationName, callable $handler): self
    {
        $this->operations[$operationName] = \Closure::fromCallable($handler);

        return $this;
    }

    protected function checkBeapp(Request $webhookRequest): bool
    {
        if ($webhookRequest->getBeappName() !== $this->configuration->getBeappName()) {
            return false;
        }

        if ($webhookRequest->getBeappId() !== $this->configuration->getBeappId()) {
            return false;
        }

        if ($webhookRequest->getBeappVersion() !== $this->configuration->getBeappVersion()) {
            return false;
        }

        return true;
    }

    protected function checkAuthorization(Request $webhookRequest): bool
    {
        if ($webhookRequest->getBeappSecret() !== $this->configuration->getBeappSecret()) {
            return false;
        }

        return true;
    }

    protected function checkOperation(Request $webhookRequest): bool
    {
        if (!array_key_exists($webhookRequest->getOperationName(), $this->operations)) {
            return false;
        }

        return true;
    }
}