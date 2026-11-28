<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryContestRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class ContestRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryContestRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryContestRepository();
    }
}
