<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

enum TransformationProduct: string
{
    case BrothsAndSauces = 'broths_and_sauces';
    case CreamsAndPurees = 'creams_and_purees';
    case JuicesAndCompotes = 'juices_and_compotes';
    case PreservesAndPickles = 'preserves_and_pickles';
    case BakingAndPastry = 'baking_and_pastry';
    case DailySpecial = 'daily_special';
    case Other = 'other';
}
