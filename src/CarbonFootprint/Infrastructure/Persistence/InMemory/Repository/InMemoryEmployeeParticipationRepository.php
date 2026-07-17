<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEmployeeParticipationRepository implements EmployeeParticipationRepository
{
    /** @param array<string, EmployeeParticipation> $employeeParticipations */
    public function __construct(
        private array $employeeParticipations = [],
    ) {}

    public function add(EmployeeParticipation $employeeParticipation): void
    {
        $this->employeeParticipations[(string) $employeeParticipation->id()] = $employeeParticipation;
    }

    public function find(EmployeeParticipationId $employeeParticipationId): ?EmployeeParticipation
    {
        return $this->employeeParticipations[(string) $employeeParticipationId] ?? null;
    }

    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): ?EmployeeParticipation
    {
        foreach ($this->employeeParticipations as $employeeParticipation) {
            $subjectId = $employeeParticipation->eventCarbonFootprintId();

            if ($subjectId instanceof EventCarbonFootprintId && $subjectId->equals($eventCarbonFootprintId)) {
                return $employeeParticipation;
            }
        }

        return null;
    }
}
