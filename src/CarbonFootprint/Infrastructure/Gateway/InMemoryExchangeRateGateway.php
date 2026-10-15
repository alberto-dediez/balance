<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Gateway;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFeed;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFeedEntry;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateGateway;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSourceUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
final class InMemoryExchangeRateGateway implements ExchangeRateGateway
{
    private const string DEFAULT_DATE = '2026-10-14';
    private const string URL = 'http://ecb.test/eurofxref-daily.xml';

    private ?\DateTimeImmutable $date = null;

    /** @var list<ExchangeRateFeedEntry> */
    private array $entries = [];

    private bool $failed = false;

    /** @param list<ExchangeRateFeedEntry> $entries */
    public function set(\DateTimeImmutable $date, array $entries): void
    {
        $this->date = $date;
        $this->entries = $entries;
    }

    public function add(string $currency, string $rate, ?\DateTimeImmutable $date = null): void
    {
        $this->date = $date ?? $this->date ?? new \DateTimeImmutable(self::DEFAULT_DATE);
        $this->entries[] = new ExchangeRateFeedEntry(currency: $currency, rate: $rate);
    }

    public function fail(): void
    {
        $this->failed = true;
    }

    public function reset(): void
    {
        $this->date = null;
        $this->entries = [];
        $this->failed = false;
    }

    public function fetch(): ExchangeRateFeed
    {
        if ($this->failed) {
            throw ExchangeRateSourceUnavailableException::fromUrl(self::URL);
        }

        return new ExchangeRateFeed(
            date: $this->date ?? new \DateTimeImmutable(self::DEFAULT_DATE),
            entries: $this->entries,
        );
    }
}
