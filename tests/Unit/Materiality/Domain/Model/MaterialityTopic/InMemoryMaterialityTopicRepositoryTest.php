<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMaterialityTopicRepositoryTest extends MaterialityTopicRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MaterialityTopicRepository
    {
        return MaterialityTopicRepositoryMother::inMemory($locale);
    }
}
