<?php

namespace App\Security\Application\RecordedEvent\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Application\RecordedEvent\RecordedEventAuthorizer;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;

class ListRecordedEventsService
{
    public function __construct(private readonly RecordedEventRepository $repository, private readonly RecordedEventAuthorizer $authorizer)
    {

    }

    public function __invoke(ListRecordedEventsRequest $request, User $performBy): ListRecordedEventsResponse
    {
        $event = new RecordedEvent(
            $performBy->id(),
            new UserRemoved(UserId::generate())
        );

        $this->authorizer->denyUnlessCanDoThat($event, Action::List, $performBy);

        $events = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListRecordedEventsResponse($events, $totalResults);
    }
}