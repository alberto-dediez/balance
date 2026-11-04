<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment\Remove;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentRequest;
use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizerMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveDistributorAssignmentServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryDistributorAssignmentRepository $repository;

    private InMemoryPropertyRepository $propertyRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = DistributorAssignmentRepositoryMother::inMemory();
        $this->propertyRepository = new InMemoryPropertyRepository();
    }

    public function testAdminRemovesWithoutNotification(): void
    {
        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate());
        $this->repository->add($assignment);

        $this->createSUT()(new RemoveDistributorAssignmentRequest($assignment->id()), UserMother::adminUser());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRemoved::class, $lastEvent);
        $this->assertFalse($lastEvent->notifyDistributor());
        $this->assertNull($this->repository->find($assignment->id()));
    }

    public function testOwnerOfAdheredAccountRemovesWithNotification(): void
    {
        $hotelId = new AccountId('hotel');
        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), $hotelId);
        $this->repository->add($assignment);

        $this->createSUT()(new RemoveDistributorAssignmentRequest($assignment->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $hotelId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRemoved::class, $lastEvent);
        $this->assertTrue($lastEvent->notifyDistributor());
    }

    public function testOwnerOfAdheredPropertyRemovesWithNotification(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel 1', 'hotel', (string) PropertyId::generate());
        $this->propertyRepository->add($property);
        $assignment = DistributorAssignmentMother::forProperty(AccountId::generate(), $property->id());
        $this->repository->add($assignment);

        $this->createSUT()(new RemoveDistributorAssignmentRequest($assignment->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('hotel')));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRemoved::class, $lastEvent);
        $this->assertTrue($lastEvent->notifyDistributor());
        $this->assertNull($this->repository->find($assignment->id()));
    }

    public function testForeignOwnerIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), new AccountId('hotel'));
        $this->repository->add($assignment);

        $this->createSUT()(new RemoveDistributorAssignmentRequest($assignment->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('foreign')));
    }

    public function testCascadeRemovalDoesNotNotify(): void
    {
        $hotelId = new AccountId('hotel');
        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), $hotelId);
        $this->repository->add($assignment);

        $this->createSUT()(new RemoveDistributorAssignmentRequest($assignment->id(), notifyDistributor: false), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $hotelId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRemoved::class, $lastEvent);
        $this->assertFalse($lastEvent->notifyDistributor());
    }

    public function testFailsWhenNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()(new RemoveDistributorAssignmentRequest(DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate())->id()), UserMother::adminUser());
    }

    private function createSUT(): RemoveDistributorAssignmentService
    {
        return new RemoveDistributorAssignmentService(
            $this->repository,
            DistributorAssignmentAuthorizerMother::create(propertyRepository: $this->propertyRepository),
        );
    }
}
