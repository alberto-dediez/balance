<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ReasonIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'reason_id';
    }

    public function getClassName(): string
    {
        return ReasonId::class;
    }
}
