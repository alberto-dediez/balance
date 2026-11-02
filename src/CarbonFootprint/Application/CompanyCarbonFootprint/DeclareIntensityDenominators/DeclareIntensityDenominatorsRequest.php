<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\Shared\Application\CommandBus\Command;

class DeclareIntensityDenominatorsRequest implements Command
{
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public ?int $occupiedRooms = null,
        public ?int $stays = null,
        public ?string $builtAreaM2 = null,
        public ?int $coversServed = null,
        public ?string $averageHeadcount = null,
        public ?string $revenueEur = null,
        public ?int $customersServed = null,
    ) {}

    public static function fromDenominators(CompanyCarbonFootprintId $companyCarbonFootprintId, IntensityDenominators $denominators): self
    {
        return new self(
            companyCarbonFootprintId: $companyCarbonFootprintId,
            occupiedRooms: $denominators->occupiedRooms(),
            stays: $denominators->stays(),
            builtAreaM2: $denominators->builtAreaM2(),
            coversServed: $denominators->coversServed(),
            averageHeadcount: $denominators->averageHeadcount(),
            revenueEur: $denominators->revenueEur(),
            customersServed: $denominators->customersServed(),
        );
    }
}
