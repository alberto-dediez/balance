<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment\Register;

use App\Property\Application\DistributorAssignment\Register\RegisterDistributorAssignmentRequest;
use App\Property\Application\DistributorAssignment\Register\RegisterDistributorAssignmentService;
use App\Property\Domain\Model\DistributorAssignment\AccountIsNotDistributorException;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRegistered;
use App\Property\Domain\Model\DistributorAssignment\DistributorCannotDistributeItselfException;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizerMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterDistributorAssignmentServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryDistributorAssignmentRepository $repository;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryAccountRepository $accountRepository;

    private Account $distributor;

    private Account $hotel;

    private Property $property;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $this->hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $this->property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $this->hotel->id());

        $this->repository = DistributorAssignmentRepositoryMother::inMemory();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->propertyRepository->add($this->property);
        $this->accountRepository = new InMemoryAccountRepository([
            (string) $this->distributor->id() => $this->distributor,
            (string) $this->hotel->id() => $this->hotel,
        ]);
    }

    public function testRegistersForAccount(): void
    {
        $response = $this->createSUT()(new RegisterDistributorAssignmentRequest($this->distributor->id(), accountId: $this->hotel->id()), UserMother::adminUser());

        $assignment = $this->repository->find($response->id);

        $this->assertNotNull($assignment);
        $this->assertFalse($assignment->isForProperty());
        $this->assertInstanceOf(DistributorAssignmentRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testRegistersForProperty(): void
    {
        $response = $this->createSUT()(new RegisterDistributorAssignmentRequest($this->distributor->id(), propertyId: $this->property->id()), UserMother::adminUser());

        $assignment = $this->repository->find($response->id);

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->isForProperty());
    }

    public function testIsIdempotent(): void
    {
        $sut = $this->createSUT();
        $request = new RegisterDistributorAssignmentRequest($this->distributor->id(), accountId: $this->hotel->id());

        $first = $sut($request, UserMother::adminUser());
        $eventsAfterFirst = count($this->spySubscriber->events());

        $second = $sut($request, UserMother::adminUser());

        $this->assertTrue($first->id->equals($second->id));
        $this->assertCount($eventsAfterFirst, $this->spySubscriber->events());
        $this->assertSame(1, $this->repository->totalResultsByFilter(new DistributorAssignmentFilter(distributorAccountId: $this->distributor->id())));
    }

    public function testFailsWhenAccountIsNotDistributor(): void
    {
        $this->expectException(AccountIsNotDistributorException::class);

        $this->createSUT()(new RegisterDistributorAssignmentRequest($this->hotel->id(), propertyId: $this->property->id()), UserMother::adminUser());
    }

    public function testCannotDistributeItselfByAccount(): void
    {
        $this->expectException(DistributorCannotDistributeItselfException::class);

        $this->createSUT()(new RegisterDistributorAssignmentRequest($this->distributor->id(), accountId: $this->distributor->id()), UserMother::adminUser());
    }

    public function testCannotDistributeItsOwnProperty(): void
    {
        $this->expectException(DistributorCannotDistributeItselfException::class);

        $own = PropertyMother::from(PropertyId::generate(), 'Own', $this->distributor->id());
        $this->propertyRepository->add($own);

        $this->createSUT()(new RegisterDistributorAssignmentRequest($this->distributor->id(), propertyId: $own->id()), UserMother::adminUser());
    }

    public function testRequiresExactlyOneTarget(): void
    {
        $sut = $this->createSUT();

        try {
            $sut(new RegisterDistributorAssignmentRequest($this->distributor->id(), accountId: $this->hotel->id(), propertyId: $this->property->id()), UserMother::adminUser());
            $this->fail('Both targets must fail');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RegisterDistributorAssignmentRequest($this->distributor->id()), UserMother::adminUser());
    }

    public function testFailsWithRemovedProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->property->remove();

        $this->createSUT()(new RegisterDistributorAssignmentRequest($this->distributor->id(), propertyId: $this->property->id()), UserMother::adminUser());
    }

    public function testNonAdminIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->hotel->id());

        $this->createSUT()(new RegisterDistributorAssignmentRequest($this->distributor->id(), accountId: $this->hotel->id()), $owner);
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->createSUT()->isHandled(new RegisterDistributorAssignmentRequest($this->distributor->id(), accountId: $this->hotel->id())));
    }

    private function createSUT(): RegisterDistributorAssignmentService
    {
        return new RegisterDistributorAssignmentService(
            $this->repository,
            $this->propertyRepository,
            $this->accountRepository,
            DistributorAssignmentAuthorizerMother::create(propertyRepository: $this->propertyRepository),
        );
    }
}
