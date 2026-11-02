<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

interface BaseYearDeclarationRepository
{
    public function add(BaseYearDeclaration $declaration): void;

    public function remove(BaseYearDeclaration $declaration): void;

    public function find(BaseYearDeclarationId $id): ?BaseYearDeclaration;

    public function findBySubject(CarbonFootprintReportSubjectType $subjectType, string $subjectId): ?BaseYearDeclaration;
}
