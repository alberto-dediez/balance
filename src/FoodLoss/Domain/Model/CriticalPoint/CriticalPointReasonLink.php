<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;

final readonly class CriticalPointReasonLink
{
    public function __construct(
        private CriticalPoint $criticalPoint,
        private ReasonId $reasonId,
        private Priority $priority,
    ) {}

    public function criticalPoint(): CriticalPoint
    {
        return $this->criticalPoint;
    }

    public function reasonId(): ReasonId
    {
        return $this->reasonId;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }
}
