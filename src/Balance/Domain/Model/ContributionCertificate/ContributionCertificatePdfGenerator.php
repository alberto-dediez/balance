<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Shared\Domain\Model\Locale\Locale;

interface ContributionCertificatePdfGenerator
{
    /** @return string Path of the freshly written temporary file. */
    public function generate(ContributionCertificateData $data, Locale $locale): string;
}
