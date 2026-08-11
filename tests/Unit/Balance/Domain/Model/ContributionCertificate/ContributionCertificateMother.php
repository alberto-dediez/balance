<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

class ContributionCertificateMother
{
    /** @param list<string> $masterSerials */
    public static function issued(
        ContributionCertificateType $type = ContributionCertificateType::Compensation,
        ?AccountId $accountId = null,
        ?ProjectContributionId $projectContributionId = null,
        string $code = 'ABCD-2345-EFGH',
        ?string $verificationToken = null,
        array $masterSerials = ['2024-b012'],
        ?float $co2eqKg = 1000.0,
        bool $beneficiaryIsCompany = false,
        ?ContributionCertificateId $id = null,
    ): ContributionCertificate {
        $contributionCertificate = new ContributionCertificate(id: $id ?? ContributionCertificateId::generate());

        $contributionCertificate->issue(
            accountId: $accountId ?? AccountId::generate(),
            projectContributionId: $projectContributionId ?? ProjectContributionId::generate(),
            code: $code,
            verificationToken: $verificationToken ?? self::randomVerificationToken(),
            type: $type,
            locale: Locale::Spanish,
            beneficiaryName: $beneficiaryIsCompany ? 'Acme SL' : 'Guest',
            beneficiaryIsCompany: $beneficiaryIsCompany,
            beneficiaryVatNumber: $beneficiaryIsCompany ? 'B12345678' : null,
            contactName: 'Guest',
            contactEmail: 'guest@example.com',
            contributorCountry: 'ES',
            co2eqKg: $co2eqKg,
            tonnes: 1.0,
            contributionAmount: 15.0,
            currency: 'EUR',
            projectId: ProjectId::generate(),
            projectName: 'Reforestación',
            projectLocation: 'Segovia',
            projectRegistryId: '2024-b012',
            masterSerials: $masterSerials,
        );

        return $contributionCertificate;
    }

    /** @param array<string, string> $files */
    public static function generated(
        ContributionCertificateType $type = ContributionCertificateType::Compensation,
        array $files = ['es' => 'account/certificate-es.pdf'],
        string $code = 'ABCD-2345-EFGH',
        ?string $verificationToken = null,
    ): ContributionCertificate {
        $contributionCertificate = self::issued(type: $type, code: $code, verificationToken: $verificationToken);
        $contributionCertificate->markGenerated($files);

        return $contributionCertificate;
    }

    public static function random(): ContributionCertificate
    {
        return self::issued(code: self::randomCode());
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
