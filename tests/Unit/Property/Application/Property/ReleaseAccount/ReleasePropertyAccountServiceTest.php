<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\ReleaseAccount;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\ReleaseAccount\ReleasePropertyAccountRequest;
use App\Property\Application\Property\ReleaseAccount\ReleasePropertyAccountService;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyAccountReleased;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ReleasePropertyAccountServiceTest extends TestCase
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

        $info = new PropertyInfo(address: 'Calle Mayor 1', rooms: 40);
        $externalId = new ExternalId(hotelbeds: 'HB-1');

        $property = $this->propertyOfAccount(AccountId::generate(), $info, $externalId);
        $repository->add($property);

        $sut = $this->createSUT($repository);

        $result = $sut(new ReleasePropertyAccountRequest(propertyId: $property->id()), UserMother::fromUsernameAndRole('admin', Role::Admin));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertNull($propertyPersisted->accountId());
        $this->assertNull($propertyPersisted->companyId());
        $this->assertSame(25, $propertyPersisted->numberOfEmployees());
        $this->assertSame(300, $propertyPersisted->dailyClients());
        $this->assertSame('Hotel', $propertyPersisted->name());
        $this->assertTrue($info->equals($propertyPersisted->info()));
        $this->assertTrue($externalId->equals($propertyPersisted->externalId()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(PropertyAccountReleased::class, $lastEvent);
        $this->assertTrue($property->id()->equals($lastEvent->propertyId()));
    }

    public function testOwnerCanReleaseOwnAccountProperty(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $property = $this->propertyOfAccount($accountId);
        $repository->add($property);

        $sut = $this->createSUT($repository);

        $sut(new ReleasePropertyAccountRequest(propertyId: $property->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $propertyPersisted = $repository->find($property->id());

        $this->assertNotNull($propertyPersisted);
        $this->assertNull($propertyPersisted->accountId());
        $this->assertNull($propertyPersisted->companyId());
        $this->assertSame(25, $propertyPersisted->numberOfEmployees());
        $this->assertSame(300, $propertyPersisted->dailyClients());
    }

    public function testOwnerCannotReleasePropertyOfAnotherAccount(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $property = $this->propertyOfAccount($accountId);
        $repository->add($property);

        $sut = $this->createSUT($repository);

        try {
            $sut(new ReleasePropertyAccountRequest(propertyId: $property->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()));
            $this->fail('UnauthorizedActionException expected');
        } catch (UnauthorizedActionException) {
        }

        $propertyPersisted = $repository->find($property->id());

        $this->assertNotNull($propertyPersisted);
        $this->assertNotNull($propertyPersisted->accountId());
        $this->assertTrue($accountId->equals($propertyPersisted->accountId()));
    }

    public function testUserCannotRelease(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $property = $this->propertyOfAccount($accountId);
        $repository->add($property);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ReleasePropertyAccountRequest(propertyId: $property->id()), UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $accountId));
    }

    public function testThrowsWhenPropertyDoesNotExist(): void
    {
        $sut = $this->createSUT(PropertyRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ReleasePropertyAccountRequest(propertyId: PropertyId::generate()), UserMother::fromUsernameAndRole('admin', Role::Admin));
    }

    public function testAdminReleasingPropertyWithoutAccountPublishesNothing(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::from(propertyId: PropertyId::generate(), name: 'Hotel', numberOfEmployees: 25, dailyClients: 300);
        $repository->add($property);

        $eventsBefore = count($this->spySubscriber->events());

        $sut = $this->createSUT($repository);

        $sut(new ReleasePropertyAccountRequest(propertyId: $property->id()), UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertCount($eventsBefore, $this->spySubscriber->events());

        $propertyPersisted = $repository->find($property->id());

        $this->assertNotNull($propertyPersisted);
        $this->assertSame(25, $propertyPersisted->numberOfEmployees());
        $this->assertSame(300, $propertyPersisted->dailyClients());
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(PropertyRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new ReleasePropertyAccountRequest(propertyId: PropertyId::generate())));
    }

    private function propertyOfAccount(AccountId $accountId, ?PropertyInfo $info = null, ?ExternalId $externalId = null): Property
    {
        return PropertyMother::from(
            propertyId: PropertyId::generate(),
            name: 'Hotel',
            accountId: $accountId,
            info: $info,
            externalId: $externalId,
            companyId: CompanyId::generate(),
            numberOfEmployees: 25,
            dailyClients: 300,
        );
    }

    private function createSUT(InMemoryPropertyRepository $repository): ReleasePropertyAccountService
    {
        return new ReleasePropertyAccountService($repository, new PropertyAuthorizer());
    }
}
