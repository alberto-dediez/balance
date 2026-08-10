<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;

class ProjectContributionRepositoryMother
{
    public static function inMemory(): InMemoryProjectContributionRepository
    {
        return new InMemoryProjectContributionRepository();
    }
}
