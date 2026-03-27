<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\DailyWaste\List;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Application\DailyWaste\List\ListDailyWastesRequest;
use App\Waste\Application\DailyWaste\List\ListDailyWastesService;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryDailyWasteRepository;
use PHPUnit\Framework\TestCase;

class ListDailyWastesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = DailyWasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $account = new Account(AccountId::generate());
        $user = UserMother::fromUsernameAndRoleAndAccount('test', Role::Owner, $account->id());

        $repository->add(DailyWasteMother::fromAccount($account->id()));
        $repository->add(DailyWasteMother::fromAccount($account->id()));
        $repository->add(DailyWasteMother::fromAccount($account->id()));
        $repository->add(DailyWasteMother::fromAccount($account->id()));

        $request = new ListDailyWastesRequest(
            accountId: $account->id(),
        );

        $result = $sut($request, $user);

        $this->assertCount(4, $result->dailyWastes);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryDailyWasteRepository $repository): ListDailyWastesService
    {
        return new ListDailyWastesService($repository, new DailyWasteAuthorizer());
    }
}
