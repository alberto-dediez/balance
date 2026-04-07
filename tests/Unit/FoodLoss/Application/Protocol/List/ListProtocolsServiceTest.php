<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Protocol\List;

use App\FoodLoss\Application\Protocol\List\ListProtocolsRequest;
use App\FoodLoss\Application\Protocol\List\ListProtocolsService;
use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProtocolsServiceTest extends TestCase
{
    public function testOwnerCanListProtocols(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $repository->add(ProtocolMother::forAccount('account-test'));
        $repository->add(ProtocolMother::forAccount('account-test'));
        $repository->add(ProtocolMother::forAccount('other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new ListProtocolsRequest();
        $request->accountId = new AccountId('account-test');

        $response = $sut($request, $user);

        $this->assertCount(2, $response->protocols);
        $this->assertEquals(2, $response->totalResults);
    }

    public function testUserCanListProtocols(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $repository->add(ProtocolMother::forAccount('account-test'));
        $repository->add(ProtocolMother::forAccount('other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));

        $request = new ListProtocolsRequest();
        $request->accountId = new AccountId('account-test');

        $response = $sut($request, $user);

        $this->assertCount(1, $response->protocols);
    }

    private function createSUT(ProtocolRepository $repository): ListProtocolsService
    {
        return new ListProtocolsService(
            repository: $repository,
            authorizer: new ProtocolAuthorizer(),
        );
    }
}
