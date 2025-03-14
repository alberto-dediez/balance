<?php

namespace App\Security\Application\RecordedEvent\List;

use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;

class ListRecordedEventsService
{
    public function __construct(private RecordedEventRepository $repository)
    {

    }

    public function __invoke(ListRecordedEventsRequest $request): ListRecordedEventsResponse
    {
        $events = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListRecordedEventsResponse($events, $totalResults);
    }
}