<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

class MitigationActionMother
{
    public static function manual(
        string $carbonFootprintId = 'carbon-footprint-id',
        string $name = 'action-name',
        int $baseYear = 2025,
        int $targetYear = 2030,
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        string $accountId = 'account-id',
    ): MitigationAction {
        return self::from(
            id: (string) MitigationActionId::generate(),
            carbonFootprintId: $carbonFootprintId,
            name: $name,
            baseYear: $baseYear,
            targetYear: $targetYear,
            subjectType: $subjectType,
            accountId: $accountId,
        );
    }

    public static function fromMeasure(
        MitigationMeasureId $measureId,
        string $carbonFootprintId = 'carbon-footprint-id',
        string $name = 'action-name',
        int $baseYear = 2025,
        int $targetYear = 2030,
    ): MitigationAction {
        return self::from(
            id: (string) MitigationActionId::generate(),
            carbonFootprintId: $carbonFootprintId,
            name: $name,
            baseYear: $baseYear,
            targetYear: $targetYear,
            measureId: $measureId,
        );
    }

    public static function withEffect(
        Source $source,
        string $itemId,
        float $reduction,
        int $baseYear = 2025,
        int $targetYear = 2030,
        string $carbonFootprintId = 'carbon-footprint-id',
    ): MitigationAction {
        $action = self::manual(carbonFootprintId: $carbonFootprintId, baseYear: $baseYear, targetYear: $targetYear);
        $action->setEffect($source, $itemId, 'item-' . $itemId, $reduction);

        return $action;
    }

    public static function withTranslation(
        Locale $locale,
        string $name,
        string $description = 'translated-description',
    ): MitigationAction {
        $action = self::manual();
        $action->translate($name, $description, $locale);

        return $action;
    }

    public static function from(
        string $id,
        string $carbonFootprintId = 'carbon-footprint-id',
        string $name = 'action-name',
        string $description = 'action-description',
        int $baseYear = 2025,
        int $targetYear = 2030,
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        string $accountId = 'account-id',
        ?MitigationMeasureId $measureId = null,
    ): MitigationAction {
        $action = new MitigationAction(
            id: new MitigationActionId($id),
        );
        $action->register(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId($accountId),
            measureId: $measureId,
            name: $name,
            description: $description,
            baseYear: $baseYear,
            targetYear: $targetYear,
        );

        return $action;
    }
}
