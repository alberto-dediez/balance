<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;

class SubjectFootprints
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
    ) {}

    /** @return list<PropertyCarbonFootprint|CompanyCarbonFootprint> */
    public function completedOf(KpiSubject $subject): array
    {
        if ('' === $subject->id) {
            return [];
        }

        $footprints = $subject->isCompany()
            ? $this->companyCarbonFootprintRepository->findByCompanyId(new CompanyId($subject->id))
            : $this->propertyCarbonFootprintRepository->findByPropertyId(new PropertyId($subject->id));

        /** @var array<int, PropertyCarbonFootprint|CompanyCarbonFootprint> $byYear */
        $byYear = [];
        foreach ($footprints as $footprint) {
            if ($footprint->isRemoved() || !$footprint->isCompleted()) {
                continue;
            }

            $current = $byYear[$footprint->year()] ?? null;

            if (null === $current || self::lastChange($footprint) > self::lastChange($current)) {
                $byYear[$footprint->year()] = $footprint;
            }
        }

        ksort($byYear);

        return array_values($byYear);
    }

    public function completedOfYear(KpiSubject $subject, int $year): PropertyCarbonFootprint|CompanyCarbonFootprint|null
    {
        foreach ($this->completedOf($subject) as $footprint) {
            if ($footprint->year() === $year) {
                return $footprint;
            }
        }

        return null;
    }

    public function previousCompleted(KpiSubject $subject, int $year): PropertyCarbonFootprint|CompanyCarbonFootprint|null
    {
        $previous = null;

        foreach ($this->completedOf($subject) as $footprint) {
            if ($footprint->year() < $year) {
                $previous = $footprint;
            }
        }

        return $previous;
    }

    private static function lastChange(PropertyCarbonFootprint|CompanyCarbonFootprint $footprint): \DateTimeImmutable
    {
        return $footprint->updatedAt() ?? $footprint->registeredAt();
    }
}
