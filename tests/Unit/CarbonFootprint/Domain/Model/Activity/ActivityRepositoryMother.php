<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;

class ActivityRepositoryMother
{
    public static function inMemory(): InMemoryActivityRepository
    {
        return new InMemoryActivityRepository();
    }
}
