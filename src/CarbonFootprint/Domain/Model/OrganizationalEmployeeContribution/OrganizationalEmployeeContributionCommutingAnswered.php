<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalEmployeeContributionCommutingAnswered extends DomainEvent
{
    protected static string $eventName = 'organizationalEmployeeContributionCommutingAnswered';

    /** @param list<CommuteAnswer> $commutes */
    public function __construct(
        private OrganizationalEmployeeContributionId $contributionId,
        private ?PropertyId $workplacePropertyId,
        private ?int $weeksPerYear,
        private array $commutes,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalEmployeeContributionId
    {
        return $this->contributionId;
    }

    public function workplacePropertyId(): ?PropertyId
    {
        return $this->workplacePropertyId;
    }

    public function weeksPerYear(): ?int
    {
        return $this->weeksPerYear;
    }

    /** @return list<CommuteAnswer> */
    public function commutes(): array
    {
        return $this->commutes;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId;
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'workplacePropertyId' => $this->workplacePropertyId instanceof PropertyId ? (string) $this->workplacePropertyId : null,
            'weeksPerYear' => $this->weeksPerYear,
            'commutes' => array_map(
                static fn(CommuteAnswer $commute): array => [
                    'mobilityId' => (string) $commute->mobilityId,
                    'distanceKm' => $commute->distanceKm,
                    'daysPerWeek' => $commute->daysPerWeek,
                    'vehicleOccupants' => $commute->vehicleOccupants,
                ],
                $this->commutes,
            ),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OrganizationalEmployeeContributionId($payload['contributionId']),
            workplacePropertyId: isset($payload['workplacePropertyId']) ? new PropertyId($payload['workplacePropertyId']) : null,
            weeksPerYear: $payload['weeksPerYear'] ?? null,
            commutes: array_values(array_map(
                static fn(array $commute): CommuteAnswer => new CommuteAnswer(
                    mobilityId: new MobilityId($commute['mobilityId']),
                    distanceKm: (float) $commute['distanceKm'],
                    daysPerWeek: (int) $commute['daysPerWeek'],
                    vehicleOccupants: (int) $commute['vehicleOccupants'],
                ),
                $payload['commutes'] ?? [],
            )),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
