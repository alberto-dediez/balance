<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Register;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Shared\Application\CommandBus\Command;

class RegisterMitigationActionRequest implements Command
{
    /**
     * @param list<array{source: Source, itemId: string, itemName: string, reduction: float, minimumReduction: float, maximumReduction: float}>|null $effects
     * @param array<string, array{name: string, description: string}>|null                                                                           $translations
     */
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
        public ?MitigationMeasureId $measureId,
        public string $name,
        public string $description,
        public int $targetYear,
        public ?int $baseYear = null,
        public ?array $effects = null,
        public ?array $translations = null,
    ) {}
}
