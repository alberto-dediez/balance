<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class DoggyBagExceptionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'doggy_bag_exception_id';
    }

    public function getClassName(): string
    {
        return DoggyBagExceptionId::class;
    }
}
