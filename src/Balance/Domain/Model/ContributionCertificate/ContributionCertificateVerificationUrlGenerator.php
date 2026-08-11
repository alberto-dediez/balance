<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Shared\Domain\Model\Locale\Locale;

interface ContributionCertificateVerificationUrlGenerator
{
    /** @return string Absolute URL of the public verification page the QR points at. */
    public function generate(string $verificationToken, Locale $locale): string;
}
