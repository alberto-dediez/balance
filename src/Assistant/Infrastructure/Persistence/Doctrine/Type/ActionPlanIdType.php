<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\Doctrine\Type;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ActionPlanIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'assistant_action_plan_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return ActionPlanId::class;
    }
}
