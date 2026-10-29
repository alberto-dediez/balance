<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\Property\Domain\Model\Property\Property;

final class OrganizationalEmployeeContributionsOverview
{
    /**
     * @param list<OrganizationalEmployeeContribution> $contributions
     * @param array<string, Employee>                  $employeesById
     * @param array<string, array<string, Emissions>>  $emissionsBySourceAndBlock
     * @param array<string, Property>                  $propertiesById
     */
    public function __construct(
        public readonly ?OrganizationalEmployeeParticipation $participation,
        public readonly array $contributions,
        public readonly array $employeesById,
        public readonly int $pendingCount,
        public readonly int $answeredCount,
        public readonly int $ignoredCount,
        public readonly ?float $answeredPercentage,
        public readonly array $emissionsBySourceAndBlock,
        public readonly array $propertiesById,
    ) {}

    public function emissionsFor(string $source): Emissions
    {
        $total = new Emissions();

        foreach ($this->emissionsBySourceAndBlock[$source] ?? [] as $emissions) {
            $total = $total->add($emissions);
        }

        return $total;
    }

    /** @return list<array{property: ?Property, emissions: Emissions}> */
    public function blockRows(string $source): array
    {
        $rows = [];

        foreach ($this->emissionsBySourceAndBlock[$source] ?? [] as $block => $emissions) {
            $rows[] = [
                'property' => '' === (string) $block ? null : ($this->propertiesById[(string) $block] ?? null),
                'emissions' => $emissions,
            ];
        }

        return $rows;
    }
}
