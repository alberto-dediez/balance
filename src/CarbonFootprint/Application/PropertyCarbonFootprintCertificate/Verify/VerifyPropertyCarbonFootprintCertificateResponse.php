<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\Security\Domain\Model\Account\AccountId;

final readonly class VerifyPropertyCarbonFootprintCertificateResponse
{
    public function __construct(
        public string $code,
        public string $propertyName,
        public string $accountName,
        public int $year,
        public int $stays,
        public float $co2eqKgPerStay,
        public float $totalCo2eqKg,
        public float $scope1Co2eqKg,
        public float $scope2Co2eqKg,
        public ?float $scope3Co2eqKg,
        public \DateTimeImmutable $issuedAt,
        public ?\DateTimeImmutable $revokedAt,
        public PropertyCarbonFootprintCertificateStatus $status,
        public bool $valid,
        public ?AccountId $accountId = null,
    ) {}
}
