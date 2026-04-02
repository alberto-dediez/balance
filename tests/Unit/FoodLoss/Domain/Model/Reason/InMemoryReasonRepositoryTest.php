<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryReasonRepositoryTest extends ReasonRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): ReasonRepository
    {
        return ReasonRepositoryMother::inMemory($locale);
    }
}
