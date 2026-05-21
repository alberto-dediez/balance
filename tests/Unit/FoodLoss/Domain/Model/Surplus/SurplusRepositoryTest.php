<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Surplus\SurplusFilter;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class SurplusRepositoryTest extends KernelTestCase
{
    public function testAddSurplusToRepository(): void
    {
        $repository = $this->createSUT();

        $surplus = SurplusMother::registeredSurplus();

        $this->assertNull($repository->find($surplus->id()));

        $repository->add($surplus);

        $recoveredSurplus = $repository->find($surplus->id());

        $this->assertNotNull($recoveredSurplus);
        $this->assertEquals($surplus->id(), $recoveredSurplus->id());
    }

    public function testRemoveSurplusFromRepository(): void
    {
        $repository = $this->createSUT();

        $surplus = SurplusMother::registeredSurplus();

        $repository->add($surplus);

        $recoveredSurplus = $repository->find($surplus->id());

        $this->assertNotNull($recoveredSurplus);

        $repository->remove($surplus);

        $this->assertNull($repository->find($surplus->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $accountId = new AccountId('account-id-test');

        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::random());
        $repository->add(SurplusMother::random());

        $filter = new SurplusFilter(accountId: $accountId);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $accountId = new AccountId('account-id-test');

        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::withAccountId($accountId));

        $filter = new SurplusFilter(accountId: $accountId, limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $surplus = SurplusMother::registeredSurplus();

        $repository->add($surplus);

        $recoveredSurplus = $repository->find($surplus->id());

        $this->assertNotNull($recoveredSurplus);

        $recoveredSurplus->remove();

        $repository->add($surplus);

        $this->assertNull($repository->find($surplus->id()));
    }

    abstract protected function createSUT(): SurplusRepository;
}
