<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\CriticalPoint\Update;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\CriticalPoint\Update\UpdateCriticalPointRequest;
use App\FoodLoss\Application\CriticalPoint\Update\UpdateCriticalPointService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateCriticalPointServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $propertyId = PropertyId::generate();
        $property = PropertyMother::from(propertyId: $propertyId, accountId: $accountId);
        $propertyRepository->add($property);

        $criticalPoint = CriticalPointMother::from(
            name: 'Original Name',
            id: (string) \App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId::generate(),
            accountId: $accountId,
            propertyId: $propertyId,
        );
        $repository->add($criticalPoint);

        $sut = $this->createSUT($repository, $propertyRepository);

        $request = new UpdateCriticalPointRequest(
            criticalPointId: $criticalPoint->id(),
            name: 'Updated Name',
            propertyId: $propertyId,
            supplyChainStage: SupplyChainStage::Storage,
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('user', \App\Security\Domain\Model\User\Role::Owner, $accountId));

        $this->assertEquals('Updated Name', $result->name);

        $criticalPointPersisted = $repository->find($result->criticalPointId);

        $this->assertNotNull($criticalPointPersisted);
        $this->assertEquals('Updated Name', $criticalPointPersisted->name());
        $this->assertEquals(SupplyChainStage::Storage, $criticalPointPersisted->supplyChainStage());
    }

    private function createSUT(CriticalPointRepository $repository, PropertyRepository $propertyRepository): UpdateCriticalPointService
    {
        return new UpdateCriticalPointService(
            criticalPointRepository: $repository,
            propertyRepository: $propertyRepository,
            authorizer: new CriticalPointAuthorizer(),
        );
    }
}
