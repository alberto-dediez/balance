<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeContributionPhaseAnswered extends DomainEvent
{
    protected static string $eventName = 'employeeContributionPhaseAnswered';

    public function __construct(
        private EmployeeContributionId $employeeContributionId,
        private Phase $phase,
        private bool $worked,
        private ?MobilityId $mobilityId,
        private ?float $distanceKm,
        private ?int $vehicleOccupants,
        private ?int $days,
        private ?int $hotelNights,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeContributionId(): EmployeeContributionId
    {
        return $this->employeeContributionId;
    }

    public function phase(): Phase
    {
        return $this->phase;
    }

    public function worked(): bool
    {
        return $this->worked;
    }

    public function mobilityId(): ?MobilityId
    {
        return $this->mobilityId;
    }

    public function distanceKm(): ?float
    {
        return $this->distanceKm;
    }

    public function vehicleOccupants(): ?int
    {
        return $this->vehicleOccupants;
    }

    public function days(): ?int
    {
        return $this->days;
    }

    public function hotelNights(): ?int
    {
        return $this->hotelNights;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeContributionId();
    }

    public function payload(): array
    {
        return [
            'employeeContributionId' => (string) $this->employeeContributionId,
            'phase' => $this->phase->value,
            'worked' => $this->worked,
            'mobilityId' => $this->mobilityId instanceof MobilityId ? (string) $this->mobilityId : null,
            'distanceKm' => $this->distanceKm,
            'vehicleOccupants' => $this->vehicleOccupants,
            'days' => $this->days,
            'hotelNights' => $this->hotelNights,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $phase = Phase::tryFrom($payload['phase']);
        assert(null !== $phase);

        return new self(
            employeeContributionId: new EmployeeContributionId($payload['employeeContributionId']),
            phase: $phase,
            worked: $payload['worked'],
            mobilityId: isset($payload['mobilityId']) ? new MobilityId($payload['mobilityId']) : null,
            distanceKm: $payload['distanceKm'] ?? null,
            vehicleOccupants: $payload['vehicleOccupants'] ?? null,
            days: $payload['days'] ?? null,
            hotelNights: $payload['hotelNights'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
