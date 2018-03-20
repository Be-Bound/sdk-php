<?php declare(strict_types=1);

namespace BeBound\SDK;

use BeBound\SDK\Push\PushRequest;

class PushClient
{
    public const CLUSTER_ENDPOINT_FORMAT = 'https://%s/push/%s_%d/%s';

    protected $configuration;
    protected $logger;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = clone $configuration;
        $this->logger = $configuration->getLogger();
    }

    public function send(PushRequest $request, string $clusterDomain)
    {
        $url = $this->createEndpointURL($clusterDomain);
        $context = $this->createContext($request);

        $response = file_get_contents($url, false, $context);
        $this->logger->info('Push message sent');

        return json_decode($response, true);
    }

    protected function createContext(PushRequest $request)
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-type: application/json',
                    'Authorization: Basic ' . $this->generateBearer(),
                ],
                'content' => \json_encode($request),
            ],
        ];

        return stream_context_create($opts);
    }

    protected function generateBearer(): string
    {
        $bearer = sprintf(
            '%s_%s:%s',
            $this->configuration->getBeappName(),
            $this->configuration->getBeappId(),
            $this->configuration->getBeappSecret()
        );

        return base64_encode($bearer);
    }

    protected function createEndpointURL(string $clusterDomain): string
    {

        return sprintf(
            self::CLUSTER_ENDPOINT_FORMAT,
            $clusterDomain,
            $this->configuration->getBeappName(),
            $this->configuration->getBeappId(),
            $this->configuration->getBeappVersion()
        );
    }
}
