<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\List;

use App\FoodLoss\Application\Surplus\List\ListSurplusesRequest;
use App\FoodLoss\Application\Surplus\List\ListSurplusesService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListSurplusesServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::withAccountId($accountId));
        $repository->add(SurplusMother::random());

        $sut = $this->createSUT($repository);

        $request = new ListSurplusesRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertCount(2, $response->surpluses);
    }

    private function createSUT(SurplusRepository $repository): ListSurplusesService
    {
        return new ListSurplusesService(
            repository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
