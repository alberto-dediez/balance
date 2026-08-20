<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Verify;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Security\Domain\Model\Account\AccountId;

final readonly class VerifyContributionCertificateResponse
{
    /**
     * @param list<string>                                                      $masterSerials
     * @param list<array{serial: string, startTonnes: float, endTonnes: float}> $creditLots
     */
    public function __construct(
        public string $code,
        public ContributionCertificateType $type,
        public string $beneficiaryName,
        public string $projectName,
        public ?string $projectLocation,
        public ?string $projectRegistryId,
        public ?float $co2eqKg,
        public array $masterSerials,
        public array $creditLots,
        public \DateTimeImmutable $issuedAt,
        public bool $valid,
        public ?AccountId $accountId = null,
    ) {}
}
