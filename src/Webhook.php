<?php declare(strict_types=1);

namespace BeBound\SDK;

use BeBound\SDK\Webhook\BaseWebhook;
use BeBound\SDK\Webhook\Failure;
use BeBound\SDK\Webhook\Request;

class Webhook extends BaseWebhook
{
    /**
     * @throws \Throwable
     */
    public function run(?Request $webhookRequest = null, bool $silent = false): string
    {
        $this->logger->info('Received incoming request');

        try {
            $webhookRequest = $webhookRequest ?? Request::fromEnvironment();

            if ($webhookRequest === null || !$this->checkBeapp($webhookRequest)) {
                $this->logger->notice('The request is not relevant for this webhook');
                return '';
            }

            $payload = $this->execute($webhookRequest);

            return $this->sendResponse($payload, self::HTTP_CODE_OK, $silent);
        } catch (Failure $e) {
            return $this->sendResponse(
                $this->formatErrorResponse($e->getMessage()),
                $e->getCode(),
                $silent
            );
        } catch (\Throwable $e) {
            if ($this->configuration->isDebug()) {
                throw $e;
            }

            return $this->sendResponse(
                $this->formatErrorResponse(Failure::BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR),
                Failure::HTTP_CODE_INTERNAL_ERROR,
                $silent
            );
        }
    }

    private function sendResponse(string $response, int $statusCode, bool $silent): string
    {
        $this->logger->info('Returns json HTTP response.');

        if ($silent) {
            return $response;
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-Length: ' . \strlen($response));
        }

        echo $response;

        return $response;
    }
}
