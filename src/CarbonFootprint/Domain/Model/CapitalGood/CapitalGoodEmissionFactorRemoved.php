<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CapitalGoodEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'capitalGoodEmissionFactorRemoved';

    public function __construct(
        private CapitalGoodId $capitalGoodId,
        private int $year,
        private EmissionFactorGroupId $emissionFactorGroupId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function capitalGoodId(): CapitalGoodId
    {
        return $this->capitalGoodId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->capitalGoodId();
    }

    public function payload(): array
    {
        return [
            'capitalGoodId' => (string) $this->capitalGoodId,
            'year' => $this->year,
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CapitalGoodEmissionFactorRemoved(
            capitalGoodId: new CapitalGoodId($payload['capitalGoodId']),
            year: $payload['year'],
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
