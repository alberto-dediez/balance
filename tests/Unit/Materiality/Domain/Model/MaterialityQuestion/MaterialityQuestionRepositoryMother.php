<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MaterialityQuestionRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMaterialityQuestionRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMaterialityQuestionRepository();
    }
}
