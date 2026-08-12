<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\Project\ProjectType;

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
