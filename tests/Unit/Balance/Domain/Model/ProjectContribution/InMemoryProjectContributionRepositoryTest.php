<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;

class InMemoryProjectContributionRepositoryTest extends ProjectContributionRepositoryTest
{
    protected function createSUT(): ProjectContributionRepository
    {
        return ProjectContributionRepositoryMother::inMemory();
    }
}
