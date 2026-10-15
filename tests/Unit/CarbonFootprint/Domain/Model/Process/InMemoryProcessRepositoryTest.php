<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryProcessRepositoryTest extends ProcessRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): ProcessRepository
    {
        return ProcessRepositoryMother::inMemory($locale);
    }
}
