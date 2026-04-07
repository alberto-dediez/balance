<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;

class InMemoryPreventionPlanRepositoryTest extends PreventionPlanRepositoryTest
{
    protected function createSUT(): PreventionPlanRepository
    {
        return PreventionPlanRepositoryMother::inMemory();
    }
}
