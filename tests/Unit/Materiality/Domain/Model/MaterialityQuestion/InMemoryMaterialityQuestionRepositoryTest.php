<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMaterialityQuestionRepositoryTest extends MaterialityQuestionRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MaterialityQuestionRepository
    {
        return MaterialityQuestionRepositoryMother::inMemory($locale);
    }
}
