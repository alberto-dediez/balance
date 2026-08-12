<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPublicLandingRepository;

class PublicLandingRepositoryMother
{
    public static function inMemory(): InMemoryPublicLandingRepository
    {
        return new InMemoryPublicLandingRepository();
    }
}
