<?php declare(strict_types=1);

namespace BeBound\SDK\Webhook;

use BeBound\SDK\Configuration;

abstract class BaseWebhook
{
    public const HTTP_CODE_OK = 200;

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

    protected function checkBeapp(WebhookRequest $webhookRequest): bool
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

    protected function checkAuthorization(WebhookRequest $webhookRequest): bool
    {
        if ($webhookRequest->getBeappSecret() !== $this->configuration->getBeappSecret()) {
            return false;
        }

        return true;
    }

    protected function checkOperation(WebhookRequest $webhookRequest): bool
    {
        if (!array_key_exists($webhookRequest->getOperationName(), $this->operations)) {
            return false;
        }

        return true;
    }

    /**
     * @throws \BeBound\SDK\Webhook\WebhookFailure
     */
    protected function execute(WebhookRequest $webhookRequest): string
    {
        if (!$this->checkAuthorization($webhookRequest)) {
            $this->logger->notice('The request authorization does not match this webhook');
            throw WebhookFailure::wrongAuthorization();
        }

        if (!$this->checkOperation($webhookRequest)) {
            $this->logger->notice(
                'No callable mapped to this operation',
                ['operation' => $webhookRequest->getOperationName()]
            );
            throw WebhookFailure::wrongOperation();
        }

        $operationResponse = $this->operations[$webhookRequest->getOperationName()]($webhookRequest);

        return \json_encode(['params' => $operationResponse]);
    }

    protected function formatErrorResponse(string $error): string
    {
        return \json_encode(['error' => $error]);
    }
}
