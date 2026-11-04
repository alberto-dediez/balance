<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Event\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Event\DistributorAssignment\RemoveAssignmentsOnAccountRemovedSubscriber;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizerMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveAssignmentsOnAccountRemovedSubscriberTest extends TestCase
{
    private InMemoryDistributorAssignmentRepository $repository;

    private InMemoryPropertyRepository $propertyRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = DistributorAssignmentRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
    }

    public function testRemovesAssignmentsAsDistributorAndAsAdheredAccount(): void
    {
        $accountId = AccountId::generate();

        $asDistributor = DistributorAssignmentMother::forAccount($accountId, AccountId::generate());
        $asAdhered = DistributorAssignmentMother::forAccount(AccountId::generate(), $accountId);
        $other = DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate());

        $this->repository->add($asDistributor);
        $this->repository->add($asAdhered);
        $this->repository->add($other);

        $spy = new SpyDomainEventSubscriber();

        $this->createSUT()->handle(new AccountRemoved($accountId));

        $this->assertTrue($asDistributor->isRemoved());
        $this->assertTrue($asAdhered->isRemoved());
        $this->assertFalse($other->isRemoved());

        $lastEvent = $spy->lastEvent();
        $this->assertInstanceOf(DistributorAssignmentRemoved::class, $lastEvent);
        $this->assertFalse($lastEvent->notifyDistributor());
    }

    public function testRemovesPropertyAssignmentsOfTheRemovedAccountProperties(): void
    {
        $accountId = AccountId::generate();
        $ownProperty = PropertyMother::from(PropertyId::generate(), 'Own', $accountId);
        $otherProperty = PropertyMother::from(PropertyId::generate(), 'Other', AccountId::generate());

        $this->propertyRepository->add($ownProperty);
        $this->propertyRepository->add($otherProperty);

        $byOwnProperty = DistributorAssignmentMother::forProperty(AccountId::generate(), $ownProperty->id());
        $byOtherProperty = DistributorAssignmentMother::forProperty(AccountId::generate(), $otherProperty->id());

        $this->repository->add($byOwnProperty);
        $this->repository->add($byOtherProperty);

        $this->createSUT()->handle(new AccountRemoved($accountId));

        $this->assertTrue($byOwnProperty->isRemoved());
        $this->assertFalse($byOtherProperty->isRemoved());
    }

    private function createSUT(): RemoveAssignmentsOnAccountRemovedSubscriber
    {
        return new RemoveAssignmentsOnAccountRemovedSubscriber(
            $this->repository,
            $this->propertyRepository,
            new RemoveDistributorAssignmentService($this->repository, DistributorAssignmentAuthorizerMother::create()),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
