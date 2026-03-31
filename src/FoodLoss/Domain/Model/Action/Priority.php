<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

enum Priority: string
{
    case Prevention = 'Prevention';

    case Transformation = 'Transformation';

    case HumanDonation = 'HumanDonation';

    case AnimalFeed = 'AnimalFeed';

    case Composting = 'Composting';
}
