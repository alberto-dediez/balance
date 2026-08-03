<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

enum ProjectType: string
{
    case EmissionCompensation = 'emission_compensation';

    case SocialProject = 'social_project';
}
