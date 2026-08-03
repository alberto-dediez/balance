<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\Project;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class ProjectRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryProjectRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryProjectRepository();
    }
}
