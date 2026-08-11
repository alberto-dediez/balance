<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Download;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Shared\Domain\Model\Locale\Locale;

final class DownloadContributionCertificateRequest
{
    public function __construct(
        public readonly ContributionCertificateId $contributionCertificateId,
        public readonly ?Locale $locale = null,
    ) {}
}
