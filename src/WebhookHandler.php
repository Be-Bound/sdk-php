<?php declare(strict_types=1);

namespace BeBound\SDK;

use BeBound\SDK\Webhook\BaseWebhook;
use BeBound\SDK\Webhook\Request;
use BeBound\SDK\Webhook\Failure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebhookHandler extends BaseWebhook implements RequestHandlerInterface
{
    public const HTTP_CODE_OK = 200;

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
            $webhookRequest = $this->parseRequest($request);

            if (!$this->checkBeapp($webhookRequest)) {
                $this->logger->notice('The request is not relevant for this webhook');
                throw Failure::wrongBeapp();
            }

            if (!$this->checkAuthorization($webhookRequest)) {
                $this->logger->notice('The request authorization does not match this webhook');
                throw Failure::wrongAuthorization();
            }

            if (!$this->checkOperation($webhookRequest)) {
                $this->logger->notice(
                    'No callable mapped to this operation',
                    ['operation' => $webhookRequest->getOperationName()]
                );
                throw Failure::wrongOperation();
            }

            $payload['params'] = $this->operations[$webhookRequest->getOperationName()]($webhookRequest);
            $this->response->getBody()->write(\json_encode($payload));

            return $this->response->withStatus(self::HTTP_CODE_OK);
        } catch (Failure $e) {
            $payload['error'] = $e->getMessage();
            $this->response->getBody()->write(\json_encode($payload));

            return $this->response->withStatus($e->getCode());
        } catch (\Throwable $e) {
            if ($this->configuration->isDebug()) {
                throw $e;
            }

            $payload['error'] = Failure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR;
            $this->response->getBody()->write(\json_encode($payload));

            return $this->response->withStatus(Failure::HTTP_CODE_INTERNAL_ERROR);
        }
    }

    private function parseRequest(RequestInterface $request): Request
    {
        return Request::fromPSR7Request($request);
    }
}
