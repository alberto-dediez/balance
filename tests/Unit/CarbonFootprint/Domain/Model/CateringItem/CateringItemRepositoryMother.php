<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCateringItemRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class CateringItemRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryCateringItemRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryCateringItemRepository();
    }
}
