<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;

interface PropertyCarbonFootprintCertificateRepository
{
    public function add(PropertyCarbonFootprintCertificate $certificate): void;

    public function remove(PropertyCarbonFootprintCertificate $certificate): void;

    public function find(PropertyCarbonFootprintCertificateId $id): ?PropertyCarbonFootprintCertificate;

    public function isRevoked(PropertyCarbonFootprintCertificateId $id): bool;

    public function findByCode(string $code): ?PropertyCarbonFootprintCertificate;

    public function findByVerificationToken(string $verificationToken): ?PropertyCarbonFootprintCertificate;

    public function findCurrentByPropertyCarbonFootprintId(PropertyCarbonFootprintId $id): ?PropertyCarbonFootprintCertificate;

    /** @return array<PropertyCarbonFootprintCertificate> */
    public function findByPropertyCarbonFootprintId(PropertyCarbonFootprintId $id): array;
}
