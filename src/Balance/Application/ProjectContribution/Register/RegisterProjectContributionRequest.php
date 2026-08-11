<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Register;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

final class RegisterProjectContributionRequest
{
    /**
     * The id is provided by the caller so that it can build the gateway return URLs
     * before the payment session exists.
     */
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly AccountId $accountId,
        public readonly ProjectId $projectId,
        public readonly string $contributorName,
        public readonly string $contributorEmail,
        public readonly string $contributorCountry,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly ProjectContributionOrigin $origin = ProjectContributionOrigin::PropertyTripCalculation,
        public readonly ?string $originId = null,
        public readonly ?float $co2eqKg = null,
        public readonly ?float $freeAmount = null,
        public readonly bool $contributorIsCompany = false,
        public readonly ?string $contributorCompanyName = null,
        public readonly ?string $contributorVatNumber = null,
        public readonly ?Locale $locale = null,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
