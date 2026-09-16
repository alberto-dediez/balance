<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Property\Domain\Model\Company\CompanyId;

interface CompanyCarbonFootprintRepository
{
    public function add(CompanyCarbonFootprint $companyCarbonFootprint): void;

    public function remove(CompanyCarbonFootprint $companyCarbonFootprint): void;

    public function find(CompanyCarbonFootprintId $companyCarbonFootprintId): ?CompanyCarbonFootprint;

    public function findByCompanyIdAndYear(CompanyId $companyId, int $year): ?CompanyCarbonFootprint;

    /** @return array<CompanyCarbonFootprint> */
    public function findByCompanyId(CompanyId $companyId): array;

    public function findByActivityId(ActivityId $activityId): ?CompanyCarbonFootprint;

    /** @return array<CompanyCarbonFootprint> */
    public function findByFilter(CompanyCarbonFootprintFilter $filter): array;

    public function totalResultsByFilter(CompanyCarbonFootprintFilter $filter): int;
}
