<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Reason;

use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;

trait GetReasonFromIdTrait
{
    private function getReasonFromId(string $reasonId, ReasonRepository $repository): Reason
    {
        $reason = $repository->find(new ReasonId($reasonId));

        if (null == $reason) {
            throw $this->createNotFoundException('Motivo no encontrado');
        }

        return $reason;
    }
}
