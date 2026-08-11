<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Verify;

final class VerifyContributionCertificateRequest
{
    public function __construct(
        public readonly string $verificationToken,
    ) {}
}
