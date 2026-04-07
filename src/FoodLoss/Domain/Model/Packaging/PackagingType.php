<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

enum PackagingType: string
{
    case Reusable = 'Reusable';

    case Compostable = 'Compostable';

    case Recyclable = 'Recyclable';

}
