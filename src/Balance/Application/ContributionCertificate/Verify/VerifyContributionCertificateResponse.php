<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Verify;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;

/**
 * What the public verification page is allowed to show. Anyone scanning the QR can read this, so
 * nothing identifying the contributor — name, company, tax id, email or amount — belongs here.
 *
 * The code is echoed back on purpose: it is the reference printed on the document, and seeing the
 * same one here is what proves the QR belongs to the paper in hand.
 */
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
