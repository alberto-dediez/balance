<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;

class ExchangeRate implements \Stringable
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private ExchangeRateId $id,
        private Unit $currency = Unit::Euro,
        private \DateTimeImmutable $date = new \DateTimeImmutable('NOW'),
        private string $rate = '0',
        private \DateTimeImmutable $fetchedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        array $events = [],
    ) {
        $this->events = $events;
    }

    public function id(): ExchangeRateId
    {
        return $this->id;
    }

    public function currency(): Unit
    {
        return $this->currency;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function rate(): string
    {
        return $this->rate;
    }

    public function fetchedAt(): \DateTimeImmutable
    {
        return $this->fetchedAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function factorToEuro(): float
    {
        return 1 / (float) $this->rate;
    }

    public function register(Unit $currency, \DateTimeImmutable $date, string $rate, \DateTimeImmutable $fetchedAt): void
    {
        if (!$currency->isCurrency() || Unit::Euro === $currency) {
            throw new \InvalidArgumentException(sprintf('Unit "%s" is not a foreign currency.', $currency->value));
        }

        $this->assertValidRate($rate);

        $this->recordApplyAndPublish(
            new ExchangeRateRegistered(
                exchangeRateId: $this->id(),
                currency: $currency,
                date: $date,
                rate: $rate,
                fetchedAt: $fetchedAt,
            ),
        );
    }

    public function refresh(\DateTimeImmutable $date, string $rate, \DateTimeImmutable $fetchedAt): bool
    {
        if ($date == $this->date && (float) $rate === (float) $this->rate) {
            return false;
        }

        $this->assertValidRate($rate);

        $this->recordApplyAndPublish(
            new ExchangeRateUpdated(
                exchangeRateId: $this->id(),
                date: $date,
                rate: $rate,
                fetchedAt: $fetchedAt,
            ),
        );

        return true;
    }

    public function applyExchangeRateRegistered(ExchangeRateRegistered $event): void
    {
        $this->currency = $event->currency();
        $this->date = $event->date();
        $this->rate = $event->rate();
        $this->fetchedAt = $event->fetchedAt();
    }

    public function applyExchangeRateUpdated(ExchangeRateUpdated $event): void
    {
        $this->date = $event->date();
        $this->rate = $event->rate();
        $this->fetchedAt = $event->fetchedAt();
        $this->updatedAt = $event->fetchedAt();
    }

    public function __toString(): string
    {
        return sprintf('%s %s (%s)', $this->currency->value, $this->rate, $this->date->format('Y-m-d'));
    }

    private function assertValidRate(string $rate): void
    {
        if (!is_numeric($rate) || (float) $rate <= 0) {
            throw new \InvalidArgumentException(sprintf('Exchange rate "%s" must be a positive number.', $rate));
        }
    }
}
