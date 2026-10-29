<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryOrganizationalEmployeeParticipationRepository implements OrganizationalEmployeeParticipationRepository
{
    /** @param array<string, OrganizationalEmployeeParticipation> $participations */
    public function __construct(
        private array $participations = [],
    ) {}

    public function add(OrganizationalEmployeeParticipation $participation): void
    {
        $this->participations[(string) $participation->id()] = $participation;
    }

    public function find(OrganizationalEmployeeParticipationId $participationId): ?OrganizationalEmployeeParticipation
    {
        return $this->participations[(string) $participationId] ?? null;
    }

    public function findByCarbonFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?OrganizationalEmployeeParticipation
    {
        foreach ($this->participations as $participation) {
            if ($participation->subjectType() === $subjectType && $participation->carbonFootprintId() === $carbonFootprintId) {
                return $participation;
            }
        }

        return null;
    }
}
