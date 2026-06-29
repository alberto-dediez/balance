<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event\List;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Application\Event\List\ListEventsRequest;
use App\EventOperations\Application\Event\List\ListEventsService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEventsServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = EventRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $repository->add(EventMother::fromNameAndAccountAndId('Event A', 'account-id-test', (string) EventId::generate()));
        $repository->add(EventMother::fromNameAndAccountAndId('Event B', 'account-id-test', (string) EventId::generate()));
        $repository->add(EventMother::fromNameAndAccountAndId('Event C', 'account-id-test', (string) EventId::generate()));

        $sut = $this->createSUT($repository);

        $request = new ListEventsRequest();
        $request->accountId = $accountId;

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertCount(3, $result->events);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(EventRepository $repository): ListEventsService
    {
        return new ListEventsService(
            repository: $repository,
            authorizer: new EventAuthorizer(),
        );
    }
}
