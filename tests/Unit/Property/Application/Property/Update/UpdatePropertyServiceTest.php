<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\Update;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Application\Property\Update\UpdatePropertyService;
use App\Property\Domain\Model\Property\PropertyUpdated;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
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
        return new UpdatePropertyService($repository, new PropertyAuthorizer());
    }
}
