<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Register;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class RegisterOperatorContributionRequest implements Command
{
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public PropertyId $propertyId,
        public string $contactEmail,
        public ?string $contactName,
        public Locale $contactLocale,
    ) {}
}
