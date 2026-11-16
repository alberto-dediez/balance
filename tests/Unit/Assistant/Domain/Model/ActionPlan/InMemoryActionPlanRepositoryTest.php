<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;

class InMemoryActionPlanRepositoryTest extends ActionPlanRepositoryTest
{
    protected function createSUT(): ActionPlanRepository
    {
        return ActionPlanRepositoryMother::inMemory();
    }
}
