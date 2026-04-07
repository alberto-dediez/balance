<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;

trait GetPreventionPlanFromIdTrait
{
    private function getPreventionPlanFromId(string $preventionPlanId, PreventionPlanRepository $repository): PreventionPlan
    {
        $preventionPlan = $repository->find(new PreventionPlanId($preventionPlanId));

        if (null == $preventionPlan) {
            throw $this->createNotFoundException('Plan de prevención no encontrado');
        }

        return $preventionPlan;
    }
}
