<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;

final class CriticalPointActionLink
{
    public function __construct(
        private CriticalPointActionLinkId $id,
        private CriticalPoint $criticalPoint,
        private ActionId $actionId,
        private Priority $priority,
    ) {}

    public function id(): CriticalPointActionLinkId
    {
        return $this->id;
    }

    public function criticalPoint(): CriticalPoint
    {
        return $this->criticalPoint;
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }
}
