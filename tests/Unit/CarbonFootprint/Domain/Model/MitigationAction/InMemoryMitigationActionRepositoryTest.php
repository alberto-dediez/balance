<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMitigationActionRepositoryTest extends MitigationActionRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MitigationActionRepository
    {
        return MitigationActionRepositoryMother::inMemory($locale);
    }
}
