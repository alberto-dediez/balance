<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

enum TransformationChannel: string
{
    case StaffMeals = 'staff_meals';
    case DiscountedSale = 'discounted_sale';
    case Freezing = 'freezing';
    case Other = 'other';
}
