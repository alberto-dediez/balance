<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\Project\ProjectType;

/**
 * The two documents European and Spanish environmental-claim rules keep apart: only the
 * compensation one may speak of offsetting emissions.
 */
enum ContributionCertificateType: string
{
    case Compensation = 'compensation';

    case SocialContribution = 'social_contribution';

    public static function fromProjectType(?ProjectType $projectType): self
    {
        return ProjectType::EmissionCompensation === $projectType ? self::Compensation : self::SocialContribution;
    }

    public function isCompensation(): bool
    {
        return self::Compensation === $this;
    }
}
