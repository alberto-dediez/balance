<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Gateway;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFeed;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFeedEntry;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateGateway;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSourceUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class EcbHttpExchangeRateGateway implements ExchangeRateGateway
{
    private const string NAMESPACE = 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $url,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetch(): ExchangeRateFeed
    {
        return $this->parse($this->download());
    }

    /** @throws ExchangeRateSourceUnavailableException */
    private function download(): string
    {
        try {
            $response = $this->httpClient->request('GET', $this->url, ['timeout' => 15]);

            if (200 !== $response->getStatusCode()) {
                throw ExchangeRateSourceUnavailableException::fromUrl($this->url);
            }

            return $response->getContent();
        } catch (ExchangeRateSourceUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ExchangeRateSourceUnavailableException::fromUrl($this->url, $e);
        }
    }

    /** @throws ExchangeRateSourceUnavailableException */
    private function parse(string $content): ExchangeRateFeed
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = new \SimpleXMLElement($content);
        } catch (\Throwable) {
            throw ExchangeRateSourceUnavailableException::malformed('Invalid XML');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $cubes = $xml->children(self::NAMESPACE)->Cube->Cube;
        $time = $cubes->attributes()['time'] ?? null;

        if (null === $time || '' === (string) $time) {
            throw ExchangeRateSourceUnavailableException::malformed('Missing time cube');
        }

        $entries = [];

        foreach ($cubes->children(self::NAMESPACE)->Cube as $cube) {
            $attributes = $cube->attributes();
            $currency = (string) ($attributes['currency'] ?? '');
            $rate = (string) ($attributes['rate'] ?? '');

            if ('' === $currency || '' === $rate) {
                $this->logger->warning('Skipping malformed ECB exchange rate entry', ['currency' => $currency, 'rate' => $rate]);

                continue;
            }

            $entries[] = new ExchangeRateFeedEntry(currency: $currency, rate: $rate);
        }

        return new ExchangeRateFeed(date: new \DateTimeImmutable((string) $time), entries: $entries);
    }
}
