<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Issue;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateCodeGenerator;

class FakeContributionCertificateCodeGenerator implements ContributionCertificateCodeGenerator
{
    private int $issued = 0;

    public function generate(): string
    {
        ++$this->issued;

        return sprintf('ABCD-EFGH-%04d', $this->issued);
    }
}
