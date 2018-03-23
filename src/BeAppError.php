<?php declare(strict_types=1);

namespace BeBound\SDK;

class BeAppError extends Webhook\WebhookFailure
{
    public function __construct(string $manifestError)
    {
        parent::__construct($manifestError, Webhook\BaseWebhook::HTTP_CODE_OK);
    }
}
