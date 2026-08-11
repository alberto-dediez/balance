<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Generate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationUrlGenerator;
use App\Shared\Domain\Model\Locale\Locale;

class FakeContributionCertificateVerificationUrlGenerator implements ContributionCertificateVerificationUrlGenerator
{
    public function generate(string $verificationToken, Locale $locale): string
    {
        return sprintf('https://example.com/%s/certificate/%s', $locale->value, $verificationToken);
    }
}
