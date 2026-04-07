<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Packaging\List;

use App\FoodLoss\Application\Packaging\List\ListPackagingsRequest;
use App\FoodLoss\Application\Packaging\List\ListPackagingsService;
use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPackagingsServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = PackagingRepositoryMother::inMemory();

        $repository->add(PackagingMother::from(name: 'Envase 1', id: 'id-1', accountId: 'account-id-test'));
        $repository->add(PackagingMother::from(name: 'Envase 2', id: 'id-2', accountId: 'account-id-test'));
        $repository->add(PackagingMother::from(name: 'Envase 3', id: 'id-3', accountId: 'other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $request = new ListPackagingsRequest(accountId: new AccountId('account-id-test'));

        $result = $sut($request, $user);

        $this->assertCount(2, $result->packagings);
        $this->assertEquals(2, $result->totalResults);
    }

    private function createSUT(PackagingRepository $repository): ListPackagingsService
    {
        return new ListPackagingsService(
            repository: $repository,
            authorizer: new PackagingAuthorizer(),
        );
    }
}
