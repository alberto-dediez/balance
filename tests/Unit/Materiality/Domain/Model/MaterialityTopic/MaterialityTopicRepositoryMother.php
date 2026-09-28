<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityTopicRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MaterialityTopicRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMaterialityTopicRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMaterialityTopicRepository();
    }
}
