<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Event\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Event\DistributorAssignment\RemoveAssignmentsOnPropertyRemovedSubscriber;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizerMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveAssignmentsOnPropertyRemovedSubscriberTest extends TestCase
{
    private InMemoryDistributorAssignmentRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = DistributorAssignmentRepositoryMother::inMemory();
    }

    public function testOwnerRemovingThePropertyRemovesItsAssignments(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel 1', 'hotel', (string) PropertyId::generate());
        $propertyRepository = new InMemoryPropertyRepository();
        $propertyRepository->add($property);

        $assignment = DistributorAssignmentMother::forProperty(AccountId::generate(), $property->id());
        $other = DistributorAssignmentMother::forProperty(AccountId::generate(), PropertyId::generate());

        $this->repository->add($assignment);
        $this->repository->add($other);

        $sut = new RemoveAssignmentsOnPropertyRemovedSubscriber(
            $this->repository,
            new RemoveDistributorAssignmentService($this->repository, DistributorAssignmentAuthorizerMother::create(propertyRepository: $propertyRepository)),
            new FakeLoggedUser(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('hotel'))),
        );

        $property->remove();

        $sut->handle(new PropertyRemoved($property->id()));

        $this->assertTrue($assignment->isRemoved());
        $this->assertFalse($other->isRemoved());
    }
}
