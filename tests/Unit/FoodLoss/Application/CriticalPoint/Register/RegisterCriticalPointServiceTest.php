<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\CriticalPoint\Register;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\CriticalPoint\Register\RegisterCriticalPointRequest;
use App\FoodLoss\Application\CriticalPoint\Register\RegisterCriticalPointService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRegistered;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterCriticalPointServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $propertyId = PropertyId::generate();
        $property = PropertyMother::from(propertyId: $propertyId, accountId: $accountId);
        $propertyRepository->add($property);

        $sut = $this->createSUT($repository, $propertyRepository);

        $request = new RegisterCriticalPointRequest(
            name: 'Critical Point 1',
            propertyId: $propertyId,
            supplyChainStage: SupplyChainStage::Receiving,
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('user', \App\Security\Domain\Model\User\Role::Owner, $accountId));

        $this->assertNotEmpty((string) $result->criticalPointId);
        $this->assertEquals('Critical Point 1', $result->name);

        $criticalPointPersisted = $repository->find($result->criticalPointId);

        $this->assertNotNull($criticalPointPersisted);
        $this->assertEquals($accountId, $criticalPointPersisted->accountId());
        $this->assertEquals($propertyId, $criticalPointPersisted->propertyId());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CriticalPointRegistered);
        $this->assertEquals('Critical Point 1', $lastEvent->name());
        $this->assertTrue($criticalPointPersisted->id()->equals($lastEvent->criticalPointId()));
    }

    public function testShouldThrowExceptionIfPropertyDoesNotBelongToUserAccount(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $otherAccountId = AccountId::generate();
        $propertyId = PropertyId::generate();
        $property = PropertyMother::from(propertyId: $propertyId, accountId: $otherAccountId);
        $propertyRepository->add($property);

        $sut = $this->createSUT($repository, $propertyRepository);

        $request = new RegisterCriticalPointRequest(
            name: 'Critical Point 1',
            propertyId: $propertyId,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property not found or does not belong to your account');

        $sut($request, UserMother::fromUsernameAndRoleAndAccount('user', \App\Security\Domain\Model\User\Role::Owner, $accountId));
    }

    private function createSUT(CriticalPointRepository $repository, PropertyRepository $propertyRepository): RegisterCriticalPointService
    {
        return new RegisterCriticalPointService(
            criticalPointRepository: $repository,
            propertyRepository: $propertyRepository,
            authorizer: new CriticalPointAuthorizer(),
        );
    }
}
