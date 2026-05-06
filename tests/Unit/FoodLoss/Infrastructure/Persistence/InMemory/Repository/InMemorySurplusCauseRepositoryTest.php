<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryTest;

class InMemorySurplusCauseRepositoryTest extends SurplusCauseRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): SurplusCauseRepository
    {
        return SurplusCauseRepositoryMother::inMemory($locale);
    }
}
