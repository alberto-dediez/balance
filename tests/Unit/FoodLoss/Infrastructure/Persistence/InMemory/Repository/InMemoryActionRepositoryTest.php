<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryTest;

class InMemoryActionRepositoryTest extends ActionRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ActionRepository
    {
        return ActionRepositoryMother::inMemory($locale);
    }
}
