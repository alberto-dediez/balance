<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

final class RemovePropertyCarbonFootprintCertificatesRequest implements Command
{
    public function __construct(
        public readonly PropertyCarbonFootprintId $propertyCarbonFootprintId,
    ) {}
}
