<?php

namespace App\Tests\Unit\Property\Application\Property\Register;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRegistered;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Application\Property\Register\RegisterPropertyResponse;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class RegisterPropertyServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, AccountId::generate());

        $sut = $this->createSUT($repository);

        $request = new RegisterPropertyRequest(
            name: 'Hotel Abaco',
            accountId: $user->accountId()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterPropertyResponse);
        $this->assertNotEmpty((string) $result->propertyId);
        $this->assertEquals(36, strlen((string) $result->propertyId));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertTrue($propertyPersisted instanceof Property);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyRegistered);
        $this->assertEquals('Hotel Abaco', $lastEvent->name());
        $this->assertTrue($propertyPersisted->id()->equals($lastEvent->propertyId()));
    }

    public function testIsHandle(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterPropertyRequest(
            name: 'A Property',
            accountId: AccountId::generate()
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryPropertyRepository $repository): RegisterPropertyService
    {
        return new RegisterPropertyService($repository, new PropertyAuthorizer());
    }
}