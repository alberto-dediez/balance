<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;

class ContributionCertificateDataMother
{
    public static function compensation(): ContributionCertificateData
    {
        return new ContributionCertificateData(
            code: 'D84Y-RK9A-2CY5',
            type: ContributionCertificateType::Compensation,
            beneficiaryName: 'Alberto Fernández Díaz',
            beneficiaryIsCompany: false,
            beneficiaryVatNumber: null,
            contactName: 'Alberto Fernández Díaz',
            contactEmail: 'alberto@example.com',
            contributorCountry: 'ES',
            co2eqKg: 1234.5,
            contributionAmount: 24.69,
            currency: 'EUR',
            projectName: 'Reforestación Sierra de Aracena',
            projectLocation: 'Sevilla',
            projectRegistryId: 'VCS-1234',
            masterSerials: ['2024-b012', '2024-b013'],
            creditLots: [
                ['serial' => '2024-b012', 'startTonnes' => 3.5, 'endTonnes' => 4.1],
                ['serial' => '2024-b013', 'startTonnes' => 0.0, 'endTonnes' => 0.6345],
            ],
            issuedAt: new \DateTimeImmutable('2026-08-11'),
            verificationUrl: 'https://example.com/certificado/verificar/2f8c1a',
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originName: 'Congreso Andalucía Sostenible 2026',
        );
    }

    public static function tripCalculation(): ContributionCertificateData
    {
        return new ContributionCertificateData(
            code: 'T55R-I66P-V77J',
            type: ContributionCertificateType::Compensation,
            beneficiaryName: 'Lucía Martín',
            beneficiaryIsCompany: false,
            beneficiaryVatNumber: null,
            contactName: 'Lucía Martín',
            contactEmail: 'lucia@example.com',
            contributorCountry: 'ES',
            co2eqKg: 182.4,
            contributionAmount: 3.65,
            currency: 'EUR',
            projectName: 'Reforestación Sierra de Aracena',
            projectLocation: 'Sevilla',
            projectRegistryId: 'VCS-1234',
            masterSerials: ['2024-b012'],
            creditLots: [
                ['serial' => '2024-b012', 'startTonnes' => 4.1, 'endTonnes' => 4.2824],
            ],
            issuedAt: new \DateTimeImmutable('2026-10-23'),
            verificationUrl: 'https://example.com/certificado/verificar/5c4e2a',
            origin: ProjectContributionOrigin::TripCalculation,
            originName: 'Valencia, España',
        );
    }

    public static function socialContribution(): ContributionCertificateData
    {
        return new ContributionCertificateData(
            code: 'A11B-C22D-E33F',
            type: ContributionCertificateType::SocialContribution,
            beneficiaryName: 'Hoteles Los Olivos S.L.',
            beneficiaryIsCompany: true,
            beneficiaryVatNumber: 'B12345678',
            contactName: 'María Ruiz',
            contactEmail: 'maria@example.com',
            contributorCountry: 'ES',
            co2eqKg: null,
            contributionAmount: 250.0,
            currency: 'EUR',
            projectName: 'Comedor social Triana',
            projectLocation: 'Sevilla',
            projectRegistryId: null,
            masterSerials: [],
            creditLots: [],
            issuedAt: new \DateTimeImmutable('2026-08-11'),
            verificationUrl: 'https://example.com/certificado/verificar/7b3d9e',
            origin: ProjectContributionOrigin::PropertyTripCalculation,
            originName: 'Hotel Los Olivos',
        );
    }

    public static function withoutOptionalBlocks(): ContributionCertificateData
    {
        return new ContributionCertificateData(
            code: 'Z99Y-X88W-V77U',
            type: ContributionCertificateType::Compensation,
            beneficiaryName: 'Guest',
            beneficiaryIsCompany: false,
            beneficiaryVatNumber: null,
            contactName: 'Guest',
            contactEmail: 'guest@example.com',
            contributorCountry: 'ES',
            co2eqKg: null,
            contributionAmount: 15.0,
            currency: 'EUR',
            projectName: 'Reforestación',
            projectLocation: null,
            projectRegistryId: null,
            masterSerials: [],
            creditLots: [],
            issuedAt: new \DateTimeImmutable('2026-08-11'),
            verificationUrl: null,
        );
    }
}
