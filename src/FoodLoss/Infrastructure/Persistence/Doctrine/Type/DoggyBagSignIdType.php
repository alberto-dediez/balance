<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class DoggyBagSignIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'doggy_bag_sign_id';
    }

    public function getClassName(): string
    {
        return DoggyBagSignId::class;
    }
}
