<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MaterialRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMaterialRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMaterialRepository();
    }
}
