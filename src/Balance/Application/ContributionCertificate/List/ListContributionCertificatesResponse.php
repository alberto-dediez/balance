<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\List;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;

class ListContributionCertificatesResponse
{
    /** @param array<ContributionCertificate> $contributionCertificates */
    public function __construct(public array $contributionCertificates = [], public int $totalResults = 0) {}
}
