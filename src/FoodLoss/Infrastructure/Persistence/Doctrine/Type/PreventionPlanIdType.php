<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PreventionPlanIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'prevention_plan_id';
    }

    public function getClassName(): string
    {
        return PreventionPlanId::class;
    }
}
