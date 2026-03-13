<?php

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\FoodLoss\Domain\Model\Action\ActionId;
use Doctrine\DBAL\Types\Type;

class ActionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'action_id';
    }

    public function getClassName(): string
    {
        return ActionId::class;
    }
}
