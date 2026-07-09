<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;

class ProviderContributionRepositoryMother
{
    public static function inMemory(): InMemoryProviderContributionRepository
    {
        return new InMemoryProviderContributionRepository();
    }
}
