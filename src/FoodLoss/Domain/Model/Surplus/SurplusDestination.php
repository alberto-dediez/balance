<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

enum SurplusDestination: string
{
    case Transformation = 'Transformation';

    case HumanDonation = 'HumanDonation';

    case AnimalFeed = 'AnimalFeed';

    case Composting = 'Composting';

    case Elimination = 'Elimination';
}
