<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Verify;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;

final readonly class VerifyContributionCertificateResponse
{
    /** @param list<string> $masterSerials */
    public function __construct(
        public string $code,
        public ContributionCertificateType $type,
        public string $projectName,
        public ?string $projectLocation,
        public ?string $projectRegistryId,
        public ?float $co2eqKg,
        public array $masterSerials,
        public \DateTimeImmutable $issuedAt,
        public bool $valid,
    ) {}
}
