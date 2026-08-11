<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

interface ContributionCertificateVerificationTokenGenerator
{
    /** @return string Secret the QR carries, long enough that holding the document does not reveal it. */
    public function generate(): string;
}
