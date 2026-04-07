<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryPreventionPlanRepository;

class PreventionPlanRepositoryMother
{
    public static function inMemory(): InMemoryPreventionPlanRepository
    {
        return new InMemoryPreventionPlanRepository();
    }
}
