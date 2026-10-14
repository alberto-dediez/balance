<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\Register;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\PropertyPlaceLocationSetter;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Property\PropertyRegistered;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

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
            accountId: $user->accountId(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->propertyId);
        $this->assertEquals(36, strlen((string) $result->propertyId));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);

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
            accountId: AccountId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryPropertyRepository $repository): RegisterPropertyService
    {
        return new RegisterPropertyService($repository, CompanyRepositoryMother::inMemory(), new PropertyAuthorizer(), new PropertyPlaceLocationSetter(new InMemoryPlaceFinder()));
    }
}
