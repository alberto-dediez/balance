<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryOrganizationalEmployeeContributionRepository implements OrganizationalEmployeeContributionRepository
{
    /** @param array<string, OrganizationalEmployeeContribution> $contributions */
    public function __construct(
        private array $contributions = [],
    ) {}

    public function add(OrganizationalEmployeeContribution $contribution): void
    {
        $this->contributions[(string) $contribution->id()] = $contribution;
    }

    public function find(OrganizationalEmployeeContributionId $contributionId): ?OrganizationalEmployeeContribution
    {
        return $this->contributions[(string) $contributionId] ?? null;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?OrganizationalEmployeeContribution
    {
        foreach ($this->contributions as $contribution) {
            if (hash_equals($contribution->token(), $token)) {
                return $contribution;
            }
        }

        return null;
    }

    /** @return list<OrganizationalEmployeeContribution> */
    public function findByCarbonFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        $results = [];

        foreach ($this->contributions as $contribution) {
            if ($contribution->subjectType() === $subjectType && $contribution->carbonFootprintId() === $carbonFootprintId) {
                $results[] = $contribution;
            }
        }

        return $results;
    }

    public function findByCarbonFootprintAndEmployee(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId, EmployeeId $employeeId): ?OrganizationalEmployeeContribution
    {
        foreach ($this->findByCarbonFootprint($subjectType, $carbonFootprintId) as $contribution) {
            $contributionEmployeeId = $contribution->employeeId();

            if ($contributionEmployeeId instanceof EmployeeId && $contributionEmployeeId->equals($employeeId)) {
                return $contribution;
            }
        }

        return null;
    }
}
