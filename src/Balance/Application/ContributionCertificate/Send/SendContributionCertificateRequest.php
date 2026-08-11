<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Send;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Shared\Domain\Model\Locale\Locale;

final class SendContributionCertificateRequest
{
    public function __construct(
        public readonly ContributionCertificateId $contributionCertificateId,
        public readonly ?string $email = null,
        public readonly ?Locale $locale = null,
    ) {}
}
