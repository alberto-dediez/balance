<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

enum MaterialityCriterion: string
{
    case Magnitude = 'magnitude';

    case Influence = 'influence';

    case Risk = 'risk';

    case StakeholderInterest = 'stakeholder_interest';

    case Outsourcing = 'outsourcing';

    case SectorGuidance = 'sector_guidance';

    case Other = 'other';
}
