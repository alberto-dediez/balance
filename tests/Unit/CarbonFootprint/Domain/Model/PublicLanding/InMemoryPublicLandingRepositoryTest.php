<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;

class InMemoryPublicLandingRepositoryTest extends PublicLandingRepositoryTest
{
    protected function createSUT(): PublicLandingRepository
    {
        return PublicLandingRepositoryMother::inMemory();
    }
}
