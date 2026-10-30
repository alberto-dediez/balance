<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateIssued;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

class PropertyCarbonFootprintCertificateMother
{
    public static function issued(
        ?AccountId $accountId = null,
        ?PropertyCarbonFootprintId $propertyCarbonFootprintId = null,
        ?string $code = null,
        ?string $verificationToken = null,
        int $stays = 41000,
        float $totalCo2eqKg = 184320.55,
        ?float $scope3Co2eqKg = 40000.0,
        ?PropertyCarbonFootprintCertificateId $id = null,
        ?\DateTimeImmutable $issuedAt = null,
    ): PropertyCarbonFootprintCertificate {
        $id ??= PropertyCarbonFootprintCertificateId::generate();
        $propertyCarbonFootprintId ??= PropertyCarbonFootprintId::generate();
        $accountId ??= AccountId::generate();
        $code ??= self::randomCode();
        $verificationToken ??= self::randomVerificationToken();

        if (null !== $issuedAt) {
            $certificate = new PropertyCarbonFootprintCertificate(
                id: $id,
                events: [
                    new PropertyCarbonFootprintCertificateIssued(
                        propertyCarbonFootprintCertificateId: $id,
                        propertyCarbonFootprintId: $propertyCarbonFootprintId,
                        propertyId: PropertyId::generate(),
                        accountId: $accountId,
                        year: 2025,
                        code: $code,
                        verificationToken: $verificationToken,
                        locale: Locale::Spanish,
                        propertyName: 'Hotel Los Olivos',
                        accountName: 'Hoteles Los Olivos S.L.',
                        stays: $stays,
                        totalCo2eqKg: $totalCo2eqKg,
                        co2eqKgPerStay: round($totalCo2eqKg / $stays, 2),
                        scope1Co2eqKg: 100000.0,
                        scope2Co2eqKg: 44320.55,
                        scope3Co2eqKg: $scope3Co2eqKg,
                        occurredOn: $issuedAt,
                    ),
                ],
            );
            $certificate->rebuild();

            return $certificate;
        }

        $certificate = new PropertyCarbonFootprintCertificate(id: $id);

        $certificate->issue(
            propertyCarbonFootprintId: $propertyCarbonFootprintId,
            propertyId: PropertyId::generate(),
            accountId: $accountId,
            year: 2025,
            code: $code,
            verificationToken: $verificationToken,
            locale: Locale::Spanish,
            propertyName: 'Hotel Los Olivos',
            accountName: 'Hoteles Los Olivos S.L.',
            stays: $stays,
            totalCo2eqKg: $totalCo2eqKg,
            scope1Co2eqKg: 100000.0,
            scope2Co2eqKg: 44320.55,
            scope3Co2eqKg: $scope3Co2eqKg,
        );

        return $certificate;
    }

    /** @param array<string, string> $files */
    public static function generated(
        ?AccountId $accountId = null,
        ?PropertyCarbonFootprintId $propertyCarbonFootprintId = null,
        ?string $code = null,
        ?string $verificationToken = null,
        int $stays = 41000,
        float $totalCo2eqKg = 184320.55,
        ?float $scope3Co2eqKg = 40000.0,
        array $files = ['es' => 'account/certificate-es.pdf'],
        ?\DateTimeImmutable $issuedAt = null,
    ): PropertyCarbonFootprintCertificate {
        $certificate = self::issued(
            accountId: $accountId,
            propertyCarbonFootprintId: $propertyCarbonFootprintId,
            code: $code,
            verificationToken: $verificationToken,
            stays: $stays,
            totalCo2eqKg: $totalCo2eqKg,
            scope3Co2eqKg: $scope3Co2eqKg,
            issuedAt: $issuedAt,
        );
        $certificate->markGenerated($files);

        return $certificate;
    }

    public static function revoked(
        ?AccountId $accountId = null,
        ?PropertyCarbonFootprintId $propertyCarbonFootprintId = null,
        ?string $code = null,
        ?string $verificationToken = null,
        int $stays = 41000,
        float $totalCo2eqKg = 184320.55,
        ?float $scope3Co2eqKg = 40000.0,
    ): PropertyCarbonFootprintCertificate {
        $certificate = self::generated(
            accountId: $accountId,
            propertyCarbonFootprintId: $propertyCarbonFootprintId,
            code: $code,
            verificationToken: $verificationToken,
            stays: $stays,
            totalCo2eqKg: $totalCo2eqKg,
            scope3Co2eqKg: $scope3Co2eqKg,
        );
        $certificate->revoke();

        return $certificate;
    }

    public static function randomCode(): string
    {
        return sprintf('%04d-%04d-%04d', rand(1000, 9999), rand(1000, 9999), rand(1000, 9999));
    }

    public static function randomVerificationToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
