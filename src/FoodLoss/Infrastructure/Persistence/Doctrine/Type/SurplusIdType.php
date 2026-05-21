<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class SurplusIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'surplus_id';
    }

    public function getClassName(): string
    {
        return SurplusId::class;
    }
}
