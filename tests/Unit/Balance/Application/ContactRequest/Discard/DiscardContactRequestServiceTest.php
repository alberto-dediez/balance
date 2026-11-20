<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContactRequest\Discard;

use App\Balance\Application\ContactRequest\ContactRequestAuthorizer;
use App\Balance\Application\ContactRequest\Discard\DiscardContactRequestRequest;
use App\Balance\Application\ContactRequest\Discard\DiscardContactRequestService;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyResolvedException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestDiscarded;
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

class DiscardContactRequestServiceTest extends TestCase
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

        $this->createSUT()(new DiscardContactRequestRequest($contactRequest->id(), $now), $admin);

        $recovered = $this->repository->find($contactRequest->id());

        $this->assertNotNull($recovered);
        $this->assertSame(ContactRequestStatus::Discarded, $recovered->status());
        $this->assertEquals($now, $recovered->resolvedAt());
        $this->assertNotNull($recovered->resolvedBy());
        $this->assertTrue($admin->id()->equals($recovered->resolvedBy()));
        $this->assertInstanceOf(ContactRequestDiscarded::class, $this->spySubscriber->lastEvent());
    }

    public function testOwnerCannotResolveAContactRequest(): void
    {
        $contactRequest = ContactRequestMother::pending();
        $this->repository->add($contactRequest);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new DiscardContactRequestRequest($contactRequest->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testResolvingAnAlreadyResolvedContactRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::discarded();
        $this->repository->add($contactRequest);

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $this->createSUT()(new DiscardContactRequestRequest($contactRequest->id()), UserMother::adminUser());
    }

    private function createSUT(): DiscardContactRequestService
    {
        return new DiscardContactRequestService($this->repository, new ContactRequestAuthorizer());
    }
}
