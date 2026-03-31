<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;

trait GetActionFromIdTrait
{
    private function getActionFromId(string $actionId, ActionRepository $repository): Action
    {
        $action = $repository->find(new ActionId($actionId));

        if (null == $action) {
            throw $this->createNotFoundException('La acción no existe');
        }

        return $action;
    }
}
