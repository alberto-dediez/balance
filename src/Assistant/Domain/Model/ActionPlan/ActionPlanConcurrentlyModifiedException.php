<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

final class ActionPlanConcurrentlyModifiedException extends \RuntimeException
{
    public function __construct(ActionPlanId $id, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Action plan %s has been modified concurrently', $id), 0, $previous);
    }
}
