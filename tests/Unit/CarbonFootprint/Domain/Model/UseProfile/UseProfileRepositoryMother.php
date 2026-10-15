<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryUseProfileRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class UseProfileRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryUseProfileRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryUseProfileRepository();
    }
}
