<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;

class ActionPlanRepositoryMother
{
    public static function inMemory(): InMemoryActionPlanRepository
    {
        return new InMemoryActionPlanRepository();
    }
}
