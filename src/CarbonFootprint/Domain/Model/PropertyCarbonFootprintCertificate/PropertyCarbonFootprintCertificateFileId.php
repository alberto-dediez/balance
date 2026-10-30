<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PropertyCarbonFootprintCertificateFileId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
