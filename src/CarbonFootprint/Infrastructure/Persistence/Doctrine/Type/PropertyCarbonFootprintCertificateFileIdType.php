<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateFileId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PropertyCarbonFootprintCertificateFileIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'property_carbon_footprint_certificate_file_id';
    }

    public function getClassName(): string
    {
        return PropertyCarbonFootprintCertificateFileId::class;
    }
}
