<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ExchangeRateRegistered extends DomainEvent
{
    protected static string $eventName = 'exchangeRateRegistered';

    public function __construct(
        private ExchangeRateId $exchangeRateId,
        private Unit $currency,
        private \DateTimeImmutable $date,
        private string $rate,
        private \DateTimeImmutable $fetchedAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function exchangeRateId(): ExchangeRateId
    {
        return $this->exchangeRateId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->exchangeRateId();
    }

    public function payload(): array
    {
        return [
            'exchangeRateId' => (string) $this->exchangeRateId,
            'currency' => $this->currency->value,
            'date' => $this->date->format('Y-m-d'),
            'rate' => $this->rate,
            'fetchedAt' => $this->fetchedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ExchangeRateRegistered(
            exchangeRateId: new ExchangeRateId($payload['exchangeRateId']),
            currency: Unit::from($payload['currency']),
            date: new \DateTimeImmutable($payload['date']),
            rate: $payload['rate'],
            fetchedAt: new \DateTimeImmutable($payload['fetchedAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
