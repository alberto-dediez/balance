<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContactRequest\MarkAsManaged;

use App\Balance\Application\ContactRequest\ContactRequestAuthorizer;
use App\Balance\Application\ContactRequest\MarkAsManaged\MarkContactRequestAsManagedRequest;
use App\Balance\Application\ContactRequest\MarkAsManaged\MarkContactRequestAsManagedService;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyResolvedException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestManaged;
use App\Balance\Domain\Model\ContactRequest\ContactRequestStatus;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContactRequestRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ContactRequest\ContactRequestMother;
use App\Tests\Unit\Balance\Domain\Model\ContactRequest\ContactRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MarkContactRequestAsManagedServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;
    private InMemoryContactRequestRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = ContactRequestRepositoryMother::inMemory();
    }

    public function testAdminResolvesAPendingContactRequest(): void
    {
        $contactRequest = ContactRequestMother::pending();
        $this->repository->add($contactRequest);
        $admin = UserMother::adminUser();
        $now = new \DateTimeImmutable('2026-11-21 12:00:00');

        $this->createSUT()(new MarkContactRequestAsManagedRequest($contactRequest->id(), $now), $admin);

        $recovered = $this->repository->find($contactRequest->id());

        $this->assertNotNull($recovered);
        $this->assertSame(ContactRequestStatus::Managed, $recovered->status());
        $this->assertEquals($now, $recovered->resolvedAt());
        $this->assertNotNull($recovered->resolvedBy());
        $this->assertTrue($admin->id()->equals($recovered->resolvedBy()));
        $this->assertInstanceOf(ContactRequestManaged::class, $this->spySubscriber->lastEvent());
    }

    public function testOwnerCannotResolveAContactRequest(): void
    {
        $contactRequest = ContactRequestMother::pending();
        $this->repository->add($contactRequest);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new MarkContactRequestAsManagedRequest($contactRequest->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testResolvingAnAlreadyResolvedContactRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::managed();
        $this->repository->add($contactRequest);

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $this->createSUT()(new MarkContactRequestAsManagedRequest($contactRequest->id()), UserMother::adminUser());
    }

    private function createSUT(): MarkContactRequestAsManagedService
    {
        return new MarkContactRequestAsManagedService($this->repository, new ContactRequestAuthorizer());
    }
}
