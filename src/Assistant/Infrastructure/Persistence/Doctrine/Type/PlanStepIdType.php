<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\Doctrine\Type;

use App\Assistant\Domain\Model\ActionPlan\PlanStepId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PlanStepIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'assistant_plan_step_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return PlanStepId::class;
    }
}
