<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

enum IncidenceType: string
{
    case MachineryFailure = 'machinery_failure';
    case NGORejection = 'ngo_rejection';
    case SpoiledFood = 'spoiled_food';
}
