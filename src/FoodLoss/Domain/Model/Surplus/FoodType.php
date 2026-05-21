<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

enum FoodType: string
{
    case Meat = 'meat';
    case Fish = 'fish';
    case Vegetables = 'vegetables';
    case Processed = 'processed';
    case Fruit = 'fruit';
    case DairyProducts = 'dairy_products';
    case Eggs = 'eggs';
    case Others = 'others';
}
