<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;

class ContributionCertificateDataMother
{
    /** A compensation certificate with every optional block filled: the longest the page ever gets. */
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
            issuedAt: new \DateTimeImmutable('2026-08-11'),
            verificationUrl: 'https://example.com/certificado/verificar/2f8c1a',
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originName: 'Congreso Andalucía Sostenible 2026',
        );
    }

    /** A company beneficiary, so the subtitle carries the VAT number and the contact person too. */
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
            issuedAt: new \DateTimeImmutable('2026-08-11'),
            verificationUrl: 'https://example.com/certificado/verificar/7b3d9e',
            origin: ProjectContributionOrigin::PropertyTripCalculation,
            originName: 'Hotel Los Olivos',
        );
    }

    /**
     * What a certificate issued before the origin was captured looks like: no flow, no location, no
     * registry entry and no lots, so every optional block of the page has to fold away.
     */
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
            issuedAt: new \DateTimeImmutable('2026-08-11'),
            verificationUrl: null,
        );
    }
}
