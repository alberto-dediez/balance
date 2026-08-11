<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Issue;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Shared\Application\CommandBus\CommandResponse;

final class IssueContributionCertificateResponse implements CommandResponse
{
    public function __construct(
        public readonly ContributionCertificate $contributionCertificate,
        public readonly bool $issued,
    ) {}
}
