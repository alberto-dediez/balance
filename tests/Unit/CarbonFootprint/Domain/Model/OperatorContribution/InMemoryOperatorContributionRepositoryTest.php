<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;

class InMemoryOperatorContributionRepositoryTest extends OperatorContributionRepositoryTest
{
    protected function createSUT(): OperatorContributionRepository
    {
        return OperatorContributionRepositoryMother::inMemory();
    }
}
