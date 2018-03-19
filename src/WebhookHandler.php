<?php declare(strict_types=1);

namespace BeBound\SDK;

use BeBound\SDK\Webhook\BaseWebhook;
use BeBound\SDK\Webhook\Request;
use BeBound\SDK\Webhook\Failure;
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
            $webhookRequest = Request::fromPSR7Request($request);

            if ($webhookRequest === null || !$this->checkBeapp($webhookRequest)) {
                $this->logger->notice('The request is not relevant for this webhook');
                throw Failure::wrongBeapp();
            }

            $payload = $this->execute($webhookRequest);
            $this->response->getBody()->write($payload);

            return $this->response->withStatus(self::HTTP_CODE_OK);
        } catch (Failure $e) {
            $this->response->getBody()->write(
                $this->formatErrorResponse($e->getMessage())
            );

            return $this->response->withStatus($e->getCode());
        } catch (\Throwable $e) {
            if ($this->configuration->isDebug()) {
                throw $e;
            }

            $this->response->getBody()->write(
                $this->formatErrorResponse(Failure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR)
            );

            return $this->response->withStatus(Failure::HTTP_CODE_INTERNAL_ERROR);
        }
    }
}
