<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;

final readonly class ContributionCertificateData
{
    /** @param list<string> $masterSerials */
    public function __construct(
        public string $code,
        public ContributionCertificateType $type,
        public string $beneficiaryName,
        public bool $beneficiaryIsCompany,
        public ?string $beneficiaryVatNumber,
        public string $contactName,
        public string $contactEmail,
        public string $contributorCountry,
        public ?float $co2eqKg,
        public float $contributionAmount,
        public string $currency,
        public string $projectName,
        public ?string $projectLocation,
        public ?string $projectRegistryId,
        public array $masterSerials,
        public \DateTimeImmutable $issuedAt,
        public ?string $verificationUrl,
        public ?ProjectContributionOrigin $origin = null,
        public ?string $originName = null,
    ) {}
}
