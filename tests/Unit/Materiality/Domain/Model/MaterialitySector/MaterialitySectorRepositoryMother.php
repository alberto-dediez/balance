<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySectorRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MaterialitySectorRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMaterialitySectorRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMaterialitySectorRepository();
    }
}
