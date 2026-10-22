<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\Update;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\PropertyPlaceLocationSetter;
use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Application\Property\Update\UpdatePropertyService;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyUpdated;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class UpdatePropertyServiceTest extends TestCase
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

        $property = PropertyMother::fromName('MyProperty');

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $request = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'Hotel',
            accountId: new AccountId('6666'),
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyUpdated);
        $this->assertEquals('Hotel', $lastEvent->name());
        $this->assertEquals('6666', (string) $lastEvent->accountId());
        $this->assertTrue($propertyPersisted->id()->equals($lastEvent->propertyId()));
        $this->assertSame(OperatingRegime::Owned, $propertyPersisted->operatingRegime());
        $this->assertSame(OperatingRegime::Owned, $result->operatingRegime);
    }

    public function testUpdateOperatingRegime(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: null,
            operatingRegime: OperatingRegime::LeasedOut,
        ), $user);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertSame(OperatingRegime::LeasedOut, $propertyPersisted->operatingRegime());
        $this->assertSame(OperatingRegime::LeasedOut, $result->operatingRegime);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(PropertyUpdated::class, $lastEvent);
        $this->assertSame(OperatingRegime::LeasedOut, $lastEvent->operatingRegime());
    }

    public function testUpdateWithoutOperatingRegimeKeepsTheCurrentOne(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');
        $property->update(name: 'MyProperty', accountId: null, operatingRegime: OperatingRegime::Franchised);

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: new AccountId('6666'),
        ), $user);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertSame(OperatingRegime::Franchised, $propertyPersisted->operatingRegime());
        $this->assertSame(OperatingRegime::Franchised, $result->operatingRegime);
    }

    public function testIsHandle(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $property = PropertyMother::random();

        $result = $sut->isHandled(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'Restaurante',
            accountId: AccountId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryPropertyRepository $repository): UpdatePropertyService
    {
        return new UpdatePropertyService($repository, CompanyRepositoryMother::inMemory(), new PropertyAuthorizer(), new PropertyPlaceLocationSetter(new InMemoryPlaceFinder()));
    }
}
