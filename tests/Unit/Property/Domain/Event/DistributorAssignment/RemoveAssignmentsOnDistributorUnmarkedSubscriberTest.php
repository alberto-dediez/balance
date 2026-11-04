<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Event\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Event\DistributorAssignment\RemoveAssignmentsOnDistributorUnmarkedSubscriber;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;
use App\Security\Domain\Model\Account\AccountDistributorStatusChanged;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizerMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveAssignmentsOnDistributorUnmarkedSubscriberTest extends TestCase
{
    private InMemoryDistributorAssignmentRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = DistributorAssignmentRepositoryMother::inMemory();
    }

    public function testIsSubscribedOnlyWhenUnmarked(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new AccountDistributorStatusChanged(AccountId::generate(), false)));
        $this->assertFalse($sut->isSubscribedTo(new AccountDistributorStatusChanged(AccountId::generate(), true)));
    }

    public function testRemovesAssignmentsOfTheUnmarkedDistributor(): void
    {
        $distributorId = AccountId::generate();

        $assignment = DistributorAssignmentMother::forAccount($distributorId, AccountId::generate());
        $other = DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate());

        $this->repository->add($assignment);
        $this->repository->add($other);

        $this->createSUT()->handle(new AccountDistributorStatusChanged($distributorId, false));

        $this->assertTrue($assignment->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    private function createSUT(): RemoveAssignmentsOnDistributorUnmarkedSubscriber
    {
        return new RemoveAssignmentsOnDistributorUnmarkedSubscriber(
            $this->repository,
            new RemoveDistributorAssignmentService($this->repository, DistributorAssignmentAuthorizerMother::create()),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
