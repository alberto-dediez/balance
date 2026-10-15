<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ExchangeRateUpdated extends DomainEvent
{
    protected static string $eventName = 'exchangeRateUpdated';

    public function __construct(
        private ExchangeRateId $exchangeRateId,
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
        return new ExchangeRateUpdated(
            exchangeRateId: new ExchangeRateId($payload['exchangeRateId']),
            date: new \DateTimeImmutable($payload['date']),
            rate: $payload['rate'],
            fetchedAt: new \DateTimeImmutable($payload['fetchedAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
