<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

interface CarbonFootprintReportRepository
{
    public function add(CarbonFootprintReport $report): void;

    public function remove(CarbonFootprintReport $report): void;

    public function find(CarbonFootprintReportId $id): ?CarbonFootprintReport;

    public function findBySubject(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?CarbonFootprintReport;

    /** @return CarbonFootprintReport[] */
    public function findByFilter(CarbonFootprintReportFilter $filter): array;

    public function totalResultsByFilter(CarbonFootprintReportFilter $filter): int;
}
