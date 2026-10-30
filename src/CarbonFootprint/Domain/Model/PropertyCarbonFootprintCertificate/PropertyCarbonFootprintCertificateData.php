<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

final readonly class PropertyCarbonFootprintCertificateData
{
    public function __construct(
        public string $code,
        public string $propertyName,
        public ?string $propertyAddress,
        public string $accountName,
        public int $year,
        public int $stays,
        public float $co2eqKgPerStay,
        public float $totalCo2eqKg,
        public float $scope1Co2eqKg,
        public float $scope2Co2eqKg,
        public ?float $scope3Co2eqKg,
        public \DateTimeImmutable $issuedAt,
        public ?string $verificationUrl,
    ) {}
}
