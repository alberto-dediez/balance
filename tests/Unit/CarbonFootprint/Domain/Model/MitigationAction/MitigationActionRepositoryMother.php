<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationActionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MitigationActionRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMitigationActionRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMitigationActionRepository();
    }
}
