<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\List;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\List\ListCompanyCarbonFootprintsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\List\ListCompanyCarbonFootprintsService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCompanyCarbonFootprintsServiceTest extends TestCase
{
    public function testListsOnlyTheFootprintsOfTheCurrentAccount(): void
    {
        $repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $repository->add(CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test'));
        $repository->add(CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test'));
        $repository->add(CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'other-account-id'));

        $sut = new ListCompanyCarbonFootprintsService($repository, new CompanyCarbonFootprintAuthorizer());

        $response = $sut(new ListCompanyCarbonFootprintsRequest(accountId: new AccountId('account-id-test')), $this->ownerUser());

        $this->assertCount(2, $response->companyCarbonFootprints);
        $this->assertSame(2, $response->totalResults);
    }

    public function testPlainUserIsDenied(): void
    {
        $sut = new ListCompanyCarbonFootprintsService(CompanyCarbonFootprintRepositoryMother::inMemory(), new CompanyCarbonFootprintAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new ListCompanyCarbonFootprintsRequest(accountId: new AccountId('account-id-test')),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test')),
        );
    }

    private function ownerUser(): \App\Security\Domain\Model\User\User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
