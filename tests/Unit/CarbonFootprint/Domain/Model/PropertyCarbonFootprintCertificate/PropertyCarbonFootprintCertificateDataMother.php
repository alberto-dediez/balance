<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateData;

class PropertyCarbonFootprintCertificateDataMother
{
    public static function standard(): PropertyCarbonFootprintCertificateData
    {
        return new PropertyCarbonFootprintCertificateData(
            code: 'D84Y-RK9A-2CY5',
            propertyName: 'Hotel Los Olivos',
            propertyAddress: 'Calle Mayor 1, 41001, Sevilla, España',
            accountName: 'Hoteles Los Olivos S.L.',
            year: 2025,
            stays: 41000,
            co2eqKgPerStay: 4.50,
            totalCo2eqKg: 184320.55,
            scope1Co2eqKg: 100000.0,
            scope2Co2eqKg: 44320.55,
            scope3Co2eqKg: 40000.0,
            issuedAt: new \DateTimeImmutable('2026-10-29'),
            verificationUrl: 'https://example.com/es/footprint-certificate/2f8c1a2f8c1a2f8c1a2f8c1a',
        );
    }

    public static function scope3Excluded(): PropertyCarbonFootprintCertificateData
    {
        return new PropertyCarbonFootprintCertificateData(
            code: 'A11B-C22D-E33F',
            propertyName: 'Hotel Los Olivos',
            propertyAddress: null,
            accountName: 'Hoteles Los Olivos S.L.',
            year: 2025,
            stays: 41000,
            co2eqKgPerStay: 3.52,
            totalCo2eqKg: 144320.55,
            scope1Co2eqKg: 100000.0,
            scope2Co2eqKg: 44320.55,
            scope3Co2eqKg: null,
            issuedAt: new \DateTimeImmutable('2026-10-29'),
            verificationUrl: 'https://example.com/es/footprint-certificate/7b3d9e7b3d9e7b3d9e7b3d9e',
        );
    }

    public static function longPropertyName(): PropertyCarbonFootprintCertificateData
    {
        return new PropertyCarbonFootprintCertificateData(
            code: 'Z99Y-X88W-V77U',
            propertyName: 'Gran Hotel Balneario y Centro de Convenciones Sierra Nevada Resort & Spa',
            propertyAddress: 'Carretera de la Sierra, kilómetro 32, Urbanización Pradollano, 18196, Monachil, Granada, España',
            accountName: 'Grupo Hotelero Internacional de Turismo Sostenible del Mediterráneo S.A.',
            year: 2024,
            stays: 1250000,
            co2eqKgPerStay: 12.34,
            totalCo2eqKg: 15425000.0,
            scope1Co2eqKg: 5000000.0,
            scope2Co2eqKg: 4425000.0,
            scope3Co2eqKg: 6000000.0,
            issuedAt: new \DateTimeImmutable('2026-10-29'),
            verificationUrl: 'https://example.com/es/footprint-certificate/5c4e2a5c4e2a5c4e2a5c4e2a',
        );
    }
}
