<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Compensate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

final class CompensateWithOwnCreditRequest implements Command
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly AccountId $accountId,
        public readonly ProjectId $projectId,
        public readonly float $co2eqKg,
        public readonly ProjectContributionOrigin $origin,
        public readonly ?string $originId,
        public readonly ?string $originName,
        public readonly string $beneficiaryName,
        public readonly string $beneficiaryEmail,
        public readonly string $beneficiaryCountry,
        public readonly bool $beneficiaryIsCompany = false,
        public readonly ?string $beneficiaryCompanyName = null,
        public readonly ?string $beneficiaryVatNumber = null,
        public readonly ?Locale $locale = null,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
