<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Generate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Shared\Application\CommandBus\Command;

final class GenerateContributionCertificateRequest implements Command
{
    public function __construct(
        public readonly ContributionCertificateId $contributionCertificateId,
    ) {}
}
