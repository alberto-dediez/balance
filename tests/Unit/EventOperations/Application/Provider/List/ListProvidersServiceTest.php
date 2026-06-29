<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Provider\List;

use App\EventOperations\Application\Provider\List\ListProvidersRequest;
use App\EventOperations\Application\Provider\List\ListProvidersService;
use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProvidersServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = ProviderRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $repository->add(ProviderMother::fromNameAndAccountAndId('Provider A', 'account-id-test', (string) ProviderId::generate()));
        $repository->add(ProviderMother::fromNameAndAccountAndId('Provider B', 'account-id-test', (string) ProviderId::generate()));
        $repository->add(ProviderMother::fromNameAndAccountAndId('Provider C', 'account-id-test', (string) ProviderId::generate()));

        $sut = $this->createSUT($repository);

        $request = new ListProvidersRequest();
        $request->accountId = $accountId;

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertCount(3, $result->providers);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(ProviderRepository $repository): ListProvidersService
    {
        return new ListProvidersService(
            repository: $repository,
            authorizer: new ProviderAuthorizer(),
        );
    }
}
