<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\UpdateOperatorContact;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\UpdateOperatorContact\UpdatePropertyOperatorContactRequest;
use App\Property\Application\Property\UpdateOperatorContact\UpdatePropertyOperatorContactService;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyOperatorContactUpdated;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdatePropertyOperatorContactServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testOwnerOfTheAccountUpdatesTheContact(): void
    {
        $accountId = AccountId::generate();
        $repository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::from(propertyId: PropertyId::generate(), accountId: $accountId);
        $repository->add($property);

        $result = $this->createSUT($repository)(
            new UpdatePropertyOperatorContactRequest($property->id(), OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::English)),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        );

        $persisted = $repository->find($property->id());

        $this->assertNotNull($persisted);
        $this->assertSame('franquicia@hotel.es', $persisted->operatorContact()->email());
        $this->assertSame('Ana', $persisted->operatorContact()->name());
        $this->assertSame(Locale::English, $persisted->operatorContact()->locale());
        $this->assertSame('franquicia@hotel.es', $result->operatorContact->email());
        $this->assertInstanceOf(PropertyOperatorContactUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testOwnerOfAnotherAccountCannotUpdateTheContact(): void
    {
        $repository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::from(propertyId: PropertyId::generate(), accountId: AccountId::generate());
        $repository->add($property);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new UpdatePropertyOperatorContactRequest($property->id(), OperatorContact::from('franquicia@hotel.es', null, null)),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    public function testUserCannotUpdateTheContact(): void
    {
        $accountId = AccountId::generate();
        $repository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::from(propertyId: PropertyId::generate(), accountId: $accountId);
        $repository->add($property);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new UpdatePropertyOperatorContactRequest($property->id(), OperatorContact::from('franquicia@hotel.es', null, null)),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $accountId),
        );
    }

    public function testUnknownPropertyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT(PropertyRepositoryMother::inMemory())(
            new UpdatePropertyOperatorContactRequest(PropertyId::generate(), OperatorContact::from('franquicia@hotel.es', null, null)),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT(PropertyRepositoryMother::inMemory())->isHandled(
            new UpdatePropertyOperatorContactRequest(PropertyId::generate(), new OperatorContact()),
        ));
    }

    private function createSUT(InMemoryPropertyRepository $repository): UpdatePropertyOperatorContactService
    {
        return new UpdatePropertyOperatorContactService($repository, new PropertyAuthorizer());
    }
}
