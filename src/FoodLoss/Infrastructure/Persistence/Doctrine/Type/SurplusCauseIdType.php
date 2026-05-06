<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class SurplusCauseIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'surplus_cause_id';
    }

    public function getClassName(): string
    {
        return SurplusCauseId::class;
    }
}
