<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOperatorContributionRepository;

class OperatorContributionRepositoryMother
{
    public static function inMemory(): InMemoryOperatorContributionRepository
    {
        return new InMemoryOperatorContributionRepository();
    }
}
