<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;

final class CriticalPointReasonLink
{
    public function __construct(
        private CriticalPointReasonLinkId $id,
        private CriticalPoint $criticalPoint,
        private ReasonId $reasonId,
        private Priority $priority,
    ) {}

    public function id(): CriticalPointReasonLinkId
    {
        return $this->id;
    }

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
