<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContactRequest\List;

use App\Balance\Application\ContactRequest\ContactRequestAuthorizer;
use App\Balance\Application\ContactRequest\List\ListContactRequestsRequest;
use App\Balance\Application\ContactRequest\List\ListContactRequestsService;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContactRequestRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\ContactRequest\ContactRequestMother;
use App\Tests\Unit\Balance\Domain\Model\ContactRequest\ContactRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListContactRequestsServiceTest extends TestCase
{
    private InMemoryContactRequestRepository $repository;

    protected function setUp(): void
    {
        $this->repository = ContactRequestRepositoryMother::inMemory();
    }

    public function testAdminListsAllContactRequests(): void
    {
        $this->repository->add(ContactRequestMother::pending());
        $this->repository->add(ContactRequestMother::managed());

        $response = $this->createSUT()(new ListContactRequestsRequest(), UserMother::adminUser());

        $this->assertCount(2, $response->contactRequests);
        $this->assertSame(2, $response->totalResults);
    }

    public function testAdminListsOnlyPendingContactRequests(): void
    {
        $this->repository->add(ContactRequestMother::pending());
        $this->repository->add(ContactRequestMother::managed());
        $this->repository->add(ContactRequestMother::discarded());

        $response = $this->createSUT()(new ListContactRequestsRequest(resolved: false), UserMother::adminUser());

        $this->assertCount(1, $response->contactRequests);
        $this->assertTrue($response->contactRequests[0]->isPending());
    }

    public function testAdminListsOnlyResolvedContactRequests(): void
    {
        $this->repository->add(ContactRequestMother::pending());
        $this->repository->add(ContactRequestMother::managed());
        $this->repository->add(ContactRequestMother::discarded());

        $response = $this->createSUT()(new ListContactRequestsRequest(resolved: true), UserMother::adminUser());

        $this->assertCount(2, $response->contactRequests);
    }

    public function testOwnerCannotListContactRequests(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListContactRequestsRequest(), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    private function createSUT(): ListContactRequestsService
    {
        return new ListContactRequestsService($this->repository, new ContactRequestAuthorizer());
    }
}
