<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagSign\List;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Application\DoggyBagSign\List\ListDoggyBagSignsRequest;
use App\FoodLoss\Application\DoggyBagSign\List\ListDoggyBagSignsService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListDoggyBagSignsServiceTest extends TestCase
{
    public function testOwnerCanListTheirSigns(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();

        $repository->add(DoggyBagSignMother::forAccount('account-test'));
        $repository->add(DoggyBagSignMother::forAccount('account-test'));
        $repository->add(DoggyBagSignMother::forAccount('other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new ListDoggyBagSignsRequest(accountId: new AccountId('account-test'));

        $result = $sut($request, $user);

        $this->assertCount(2, $result->doggyBagSigns);
        $this->assertEquals(2, $result->totalResults);
    }

    public function testUserCanListSigns(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();

        $repository->add(DoggyBagSignMother::forAccount('account-test'));
        $repository->add(DoggyBagSignMother::forAccount('account-test'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));

        $request = new ListDoggyBagSignsRequest(accountId: new AccountId('account-test'));

        $result = $sut($request, $user);

        $this->assertCount(2, $result->doggyBagSigns);
    }

    private function createSUT(DoggyBagSignRepository $repository): ListDoggyBagSignsService
    {
        return new ListDoggyBagSignsService(
            repository: $repository,
            authorizer: new DoggyBagSignAuthorizer(),
        );
    }
}
