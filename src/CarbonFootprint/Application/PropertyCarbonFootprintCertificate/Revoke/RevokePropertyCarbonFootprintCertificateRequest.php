<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\Shared\Application\CommandBus\Command;

final class RevokePropertyCarbonFootprintCertificateRequest implements Command
{
    public function __construct(
        public readonly PropertyCarbonFootprintCertificateId $propertyCarbonFootprintCertificateId,
    ) {}
}
