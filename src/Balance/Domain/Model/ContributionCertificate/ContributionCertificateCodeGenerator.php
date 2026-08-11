<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

interface ContributionCertificateCodeGenerator
{
    /** @return string Unguessable public code in XXXX-XXXX-XXXX form. */
    public function generate(): string;
}
