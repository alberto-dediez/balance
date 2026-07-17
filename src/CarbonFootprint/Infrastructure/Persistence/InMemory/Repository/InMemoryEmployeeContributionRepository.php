<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEmployeeContributionRepository implements EmployeeContributionRepository
{
    /** @param array<string, EmployeeContribution> $employeeContributions */
    public function __construct(
        private array $employeeContributions = [],
    ) {}

    public function add(EmployeeContribution $employeeContribution): void
    {
        $this->employeeContributions[(string) $employeeContribution->id()] = $employeeContribution;
    }

    public function find(EmployeeContributionId $employeeContributionId): ?EmployeeContribution
    {
        return $this->employeeContributions[(string) $employeeContributionId] ?? null;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?EmployeeContribution
    {
        foreach ($this->employeeContributions as $employeeContribution) {
            if (hash_equals($employeeContribution->token(), $token)) {
                return $employeeContribution;
            }
        }

        return null;
    }

    /** @return list<EmployeeContribution> */
    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): array
    {
        $results = [];

        foreach ($this->employeeContributions as $employeeContribution) {
            $subjectId = $employeeContribution->eventCarbonFootprintId();

            if ($subjectId instanceof EventCarbonFootprintId && $subjectId->equals($eventCarbonFootprintId)) {
                $results[] = $employeeContribution;
            }
        }

        return $results;
    }

    public function findByEventCarbonFootprintIdAndEmployeeId(EventCarbonFootprintId $eventCarbonFootprintId, EmployeeId $employeeId): ?EmployeeContribution
    {
        foreach ($this->findByEventCarbonFootprintId($eventCarbonFootprintId) as $employeeContribution) {
            $subjectEmployeeId = $employeeContribution->employeeId();

            if ($subjectEmployeeId instanceof EmployeeId && $subjectEmployeeId->equals($employeeId)) {
                return $employeeContribution;
            }
        }

        return null;
    }
}
