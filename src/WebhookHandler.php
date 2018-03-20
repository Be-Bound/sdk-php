<?php declare(strict_types=1);

namespace BeBound\SDK;

use BeBound\SDK\Webhook\BaseWebhook;
use BeBound\SDK\Webhook\WebhookRequest;
use BeBound\SDK\Webhook\WebhookFailure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebhookHandler extends BaseWebhook implements RequestHandlerInterface
{
    private $response;

    public function __construct(Configuration $configuration, ResponseInterface $response)
    {
        $this->response = $response;

        parent::__construct($configuration);
    }

    /**
     * @throws \Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Handle incoming request');

        try {
            $webhookRequest = WebhookRequest::fromPSR7Request($request);

            if ($webhookRequest === null || !$this->checkBeapp($webhookRequest)) {
                $this->logger->notice('The request is not relevant for this webhook');
                throw WebhookFailure::wrongBeapp();
            }

            $payload = $this->execute($webhookRequest);
            $this->response->getBody()->write($payload);

            return $this->response->withStatus(self::HTTP_CODE_OK);
        } catch (WebhookFailure $e) {
            $this->response->getBody()->write(
                $this->formatErrorResponse($e->getMessage())
            );

            return $this->response->withStatus($e->getCode());
        } catch (\Throwable $e) {
            if ($this->configuration->isDebug()) {
                throw $e;
            }

            $this->response->getBody()->write(
                $this->formatErrorResponse(WebhookFailure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR)
            );

            return $this->response->withStatus(WebhookFailure::HTTP_CODE_INTERNAL_ERROR);
        }
    }
}
