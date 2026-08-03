<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\Project;

use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryProjectRepositoryTest extends ProjectRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): ProjectRepository
    {
        return ProjectRepositoryMother::inMemory($locale);
    }
}
