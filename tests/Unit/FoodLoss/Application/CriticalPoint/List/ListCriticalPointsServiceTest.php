<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\CriticalPoint\List;

use App\Security\Domain\Model\User\Role;
use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\CriticalPoint\List\ListCriticalPointsRequest;
use App\FoodLoss\Application\CriticalPoint\List\ListCriticalPointsService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCriticalPointsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $otherAccountId = AccountId::generate();

        $repository->add(CriticalPointMother::from(name: 'My CP 1', id: '1', accountId: $accountId));
        $repository->add(CriticalPointMother::from(name: 'My CP 2', id: '2', accountId: $accountId));
        $repository->add(CriticalPointMother::from(name: 'Other CP', id: '3', accountId: $otherAccountId));

        $sut = $this->createSUT($repository);

        $request = new ListCriticalPointsRequest(
            accountId: $accountId,
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId));

        $this->assertCount(2, $result->criticalPoints);
        $this->assertEquals(2, $result->totalResults);
        $this->assertEquals('My CP 1', $result->criticalPoints[0]->name());
    }

    private function createSUT(CriticalPointRepository $repository): ListCriticalPointsService
    {
        return new ListCriticalPointsService(
            criticalPointRepository: $repository,
            authorizer: new CriticalPointAuthorizer(),
        );
    }
}
