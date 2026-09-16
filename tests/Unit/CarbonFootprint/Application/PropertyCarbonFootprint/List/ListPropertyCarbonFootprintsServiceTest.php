<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\List;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\List\ListPropertyCarbonFootprintsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\List\ListPropertyCarbonFootprintsService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPropertyCarbonFootprintsServiceTest extends TestCase
{
    public function testListsOnlyTheFootprintsOfTheCurrentAccount(): void
    {
        $repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $repository->add(PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test'));
        $repository->add(PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test'));
        $repository->add(PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'other-account-id'));

        $sut = new ListPropertyCarbonFootprintsService($repository, new PropertyCarbonFootprintAuthorizer());

        $response = $sut(new ListPropertyCarbonFootprintsRequest(accountId: new AccountId('account-id-test')), $this->ownerUser());

        $this->assertCount(2, $response->propertyCarbonFootprints);
        $this->assertSame(2, $response->totalResults);
    }

    public function testPlainUserIsDenied(): void
    {
        $sut = new ListPropertyCarbonFootprintsService(PropertyCarbonFootprintRepositoryMother::inMemory(), new PropertyCarbonFootprintAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new ListPropertyCarbonFootprintsRequest(accountId: new AccountId('account-id-test')),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test')),
        );
    }

    private function ownerUser(): \App\Security\Domain\Model\User\User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
