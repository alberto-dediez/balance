<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\List;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEventsService
{
    public function __construct(private EventRepository $repository, private EventAuthorizer $authorizer) {}

    public function __invoke(ListEventsRequest $request, User $user): ListEventsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Event(
            id: EventId::generate(),
            accountId: $request->accountId,
        ), SecurityAction::List, $user);

        $events = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListEventsResponse($events, $totalResults);
    }
}
