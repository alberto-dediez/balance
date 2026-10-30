<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\Shared\Application\CommandBus\CommandResponse;

final class IssuePropertyCarbonFootprintCertificateResponse implements CommandResponse
{
    public function __construct(
        public readonly PropertyCarbonFootprintCertificate $certificate,
    ) {}
}
