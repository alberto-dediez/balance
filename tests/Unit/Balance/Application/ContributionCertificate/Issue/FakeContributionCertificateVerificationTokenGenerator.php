<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Issue;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationTokenGenerator;

class FakeContributionCertificateVerificationTokenGenerator implements ContributionCertificateVerificationTokenGenerator
{
    private int $issued = 0;

    public function generate(): string
    {
        ++$this->issued;

        return sprintf('verification-token-%04d', $this->issued);
    }
}
