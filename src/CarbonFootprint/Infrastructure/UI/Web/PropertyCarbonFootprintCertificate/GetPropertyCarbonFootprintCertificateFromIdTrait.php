<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;

trait GetPropertyCarbonFootprintCertificateFromIdTrait
{
    private function getPropertyCarbonFootprintCertificateFromId(string $certificateId, PropertyCarbonFootprintCertificateRepository $repository): PropertyCarbonFootprintCertificate
    {
        $certificate = $repository->find(new PropertyCarbonFootprintCertificateId($certificateId));

        if (null === $certificate) {
            throw $this->createNotFoundException('Certificado no encontrado');
        }

        return $certificate;
    }
}
