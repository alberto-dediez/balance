<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\List;

use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Application\Alert\List\ListAlertsRequest;
use App\Waste\Application\Alert\List\ListAlertsService;
use App\Waste\Domain\Model\Alert\AlertRepository;
use PHPUnit\Framework\TestCase;

class ListAlertsServiceTest extends TestCase
{
    public function testAdminCanListAllAlerts(): void
    {
        $repository = AlertRepositoryMother::inMemory();
        $repository->add(AlertMother::fromIdAndAccount('alert-1', 'account-1'));
        $repository->add(AlertMother::fromIdAndAccount('alert-2', 'account-2'));
        $repository->add(AlertMother::fromIdAndAccount('alert-3', 'account-3'));

        $sut = $this->createSUT($repository);

        $request = new ListAlertsRequest(limit: 0);
        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->alerts);
        $this->assertEquals(3, $result->totalResults);
    }

    public function testOwnerListsOnlyOwnAccountAlerts(): void
    {
        $repository = AlertRepositoryMother::inMemory();
        $repository->add(AlertMother::fromIdAndAccount('alert-1', 'my-account'));
        $repository->add(AlertMother::fromIdAndAccount('alert-2', 'my-account'));
        $repository->add(AlertMother::fromIdAndAccount('alert-3', 'other-account'));

        $sut = $this->createSUT($repository);

        $accountId = new AccountId('my-account');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, $accountId);

        $request = new ListAlertsRequest(accountId: $accountId, limit: 0);
        $result = $sut($request, $user);

        $this->assertCount(2, $result->alerts);
    }

    private function createSUT(AlertRepository $repository): ListAlertsService
    {
        return new ListAlertsService(
            repository: $repository,
            authorizer: new AlertAuthorizer(),
        );
    }
}
