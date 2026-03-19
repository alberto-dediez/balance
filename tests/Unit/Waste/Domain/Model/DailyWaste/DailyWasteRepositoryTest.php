<?php

namespace App\Tests\Unit\Waste\Domain\Model\DailyWaste;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;

abstract class DailyWasteRepositoryTest extends KernelTestCase
{
    public function testAddDailyWasteToRepository(): void
    {   
        $repository = $this->createSUT();

        $dailyWaste = DailyWasteMother::random();

        $this->assertNull($repository->find($dailyWaste->id()));

        $repository->add($dailyWaste);

        $recoverDailyWaste = $repository->find($dailyWaste->id());

        $this->assertNotNull($recoverDailyWaste);
        $this->assertEquals($dailyWaste->id(), $recoverDailyWaste->id());
    }

    public function testRemoveDailyWasteToRepository(): void
    {   
        $repository = $this->createSUT();

        $dailyWaste = DailyWasteMother::random();

        $repository->add($dailyWaste);

        $recoverDailyWaste = $repository->find($dailyWaste->id());

        $this->assertNotNull($recoverDailyWaste);
        $this->assertEquals($dailyWaste->id(), $recoverDailyWaste->id());

        $repository->remove($dailyWaste);

        $this->assertNull($repository->find($dailyWaste->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::fromAccount(
                $accountId
            )
        );

        $filter = new DailyWasteFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DailyWasteFilter(
            accountId: $accountId
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByDay(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $dailyFromAccount = DailyWasteMother::fromAccount(
            $accountId
        );

        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            $dailyFromAccount
        );

        new DailyWasteFilter();

        $result = $repository->findByDay(
            day: $dailyFromAccount->day(),
            propertyId: $dailyFromAccount->propertyId(),
            wasteId: $dailyFromAccount->wasteId(),
            purpose: $dailyFromAccount->purpose(),
            wasteManagerId: $dailyFromAccount->wasteManagerId(),
            accountId: $dailyFromAccount->accountId()
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->id()->equals($dailyFromAccount->id()));
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::random()
        );
        $repository->add(
            DailyWasteMother::fromAccount(
                $accountId
            )
        );

        $filter = new DailyWasteFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DailyWasteFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    abstract protected function createSUT(): DailyWasteRepository;
}
