<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Issue;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

final class IssueContributionCertificateRequest implements Command
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly ?Locale $locale = null,
    ) {}
}
