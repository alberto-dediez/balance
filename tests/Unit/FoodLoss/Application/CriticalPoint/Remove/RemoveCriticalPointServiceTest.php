<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\CriticalPoint\Remove;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\CriticalPoint\Remove\RemoveCriticalPointRequest;
use App\FoodLoss\Application\CriticalPoint\Remove\RemoveCriticalPointService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemoveCriticalPointServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $criticalPoint = CriticalPointMother::from(
            name: 'To be removed',
            id: (string) CriticalPointId::generate(),
            accountId: $accountId,
        );
        $repository->add($criticalPoint);

        $sut = $this->createSUT($repository);

        $request = new RemoveCriticalPointRequest(
            criticalPointId: $criticalPoint->id(),
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId));

        $this->assertEquals($criticalPoint->id(), $result->criticalPointId);

        $criticalPointPersisted = $repository->find($result->criticalPointId);

        $this->assertNull($criticalPointPersisted); // Because in-memory find checks isRemoved()
    }

    private function createSUT(CriticalPointRepository $repository): RemoveCriticalPointService
    {
        return new RemoveCriticalPointService(
            criticalPointRepository: $repository,
            authorizer: new CriticalPointAuthorizer(),
        );
    }
}
