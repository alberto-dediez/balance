<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Security\Domain\Model\Account\AccountId;

class BaseYearDeclarationMother
{
    public static function declared(
        string $subjectId = 'property-1',
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        string $accountId = 'account-1',
        int $year = 2024,
        string $total = '100',
        string $threshold = '5',
        ?IntensityIndicator $primaryIndicator = null,
    ): BaseYearDeclaration {
        return BaseYearDeclaration::declare(
            BaseYearDeclarationId::generate(),
            $subjectType,
            $subjectId,
            new AccountId($accountId),
            $year,
            'Primer año con inventario completo',
            $threshold,
            $primaryIndicator,
            $total,
        );
    }
}
