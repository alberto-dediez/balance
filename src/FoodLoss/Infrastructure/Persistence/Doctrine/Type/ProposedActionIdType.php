<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProposedActionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'proposed_action_id';
    }

    public function getClassName(): string
    {
        return ProposedActionId::class;
    }
}
