<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProcessRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class ProcessRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryProcessRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryProcessRepository();
    }
}
