<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\WasteManager\List;

use App\FoodLoss\Application\WasteManager\List\ListWasteManagersRequest;
use App\FoodLoss\Application\WasteManager\List\ListWasteManagersService;
use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListWasteManagersServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = WasteManagerRepositoryMother::inMemory();

        $repository->add(WasteManagerMother::fromAccount('account-id-test'));
        $repository->add(WasteManagerMother::fromAccount('account-id-test'));
        $repository->add(WasteManagerMother::fromAccount('other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);

        $request = new ListWasteManagersRequest(accountId: $accountId);

        $result = $sut($request, $user);

        $this->assertCount(2, $result->wasteManagers);
        $this->assertEquals(2, $result->totalResults);
    }

    private function createSUT(WasteManagerRepository $repository): ListWasteManagersService
    {
        return new ListWasteManagersService(
            repository: $repository,
            authorizer: new WasteManagerAuthorizer(),
        );
    }
}
