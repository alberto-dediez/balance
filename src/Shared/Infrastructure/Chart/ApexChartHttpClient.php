<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Chart;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApexChartHttpClient implements ApexChartClient
{
    private const int MAX_RETRIES = 3;
    private const int RETRY_SLEEP = 15;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $endpoint,
    ) {}

    /** @param array<string, mixed> $options */
    public function generate(array $options, int $retry = 0): string
    {
        try {
            $response = $this->httpClient->request('POST', $this->endpoint, ['json' => $options]);
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \RuntimeException(sprintf('ApexChart API returned HTTP %d', $statusCode));
            }

            return $response->getContent();
        } catch (\Throwable $e) {
            if ($retry < self::MAX_RETRIES - 1) {
                sleep(self::RETRY_SLEEP);

                return $this->generate($options, $retry + 1);
            }

            throw new \RuntimeException(sprintf('ApexChart API failed after %d attempts: %s', self::MAX_RETRIES, $e->getMessage()), previous: $e);
        }
    }
}
