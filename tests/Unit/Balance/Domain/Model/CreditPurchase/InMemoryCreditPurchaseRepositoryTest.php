<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;

class InMemoryCreditPurchaseRepositoryTest extends CreditPurchaseRepositoryTest
{
    private ?InMemoryProjectContributionRepository $projectContributionRepository = null;

    protected function createSUT(): CreditPurchaseRepository
    {
        return new InMemoryCreditPurchaseRepository(projectContributionRepository: $this->createProjectContributionRepository());
    }

    protected function createProjectContributionRepository(): ProjectContributionRepository
    {
        return $this->projectContributionRepository ??= ProjectContributionRepositoryMother::inMemory();
    }
}
