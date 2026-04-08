<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagException\Register;

use App\FoodLoss\Application\DoggyBagException\DoggyBagExceptionAuthorizer;
use App\FoodLoss\Application\DoggyBagException\Register\RegisterDoggyBagExceptionRequest;
use App\FoodLoss\Application\DoggyBagException\Register\RegisterDoggyBagExceptionService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRegistered;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterDoggyBagExceptionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = DoggyBagExceptionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $criticalPointId = CriticalPointId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new \App\Security\Domain\Model\Account\AccountId('account-id'));

        $request = new RegisterDoggyBagExceptionRequest(
            description: 'Doggy Bag Exception 1',
            criticalPointId: $criticalPointId,
            accountId: $user->accountId(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->doggyBagExceptionId);
        $this->assertEquals(36, strlen((string) $result->doggyBagExceptionId));
        $this->assertEquals('Doggy Bag Exception 1', $result->description);

        $doggyBagExceptionPersisted = $repository->find($result->doggyBagExceptionId);

        $this->assertNotNull($doggyBagExceptionPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DoggyBagExceptionRegistered);
        $this->assertEquals('Doggy Bag Exception 1', $lastEvent->description());
        $this->assertTrue($doggyBagExceptionPersisted->id()->equals($lastEvent->doggyBagExceptionId()));
    }

    private function createSUT(DoggyBagExceptionRepository $repository): RegisterDoggyBagExceptionService
    {
        return new RegisterDoggyBagExceptionService(
            doggyBagExceptionRepository: $repository,
            authorizer: new DoggyBagExceptionAuthorizer(),
        );
    }
}
