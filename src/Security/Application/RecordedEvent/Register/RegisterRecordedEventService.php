<?php

namespace App\Security\Application\RecordedEvent\Register;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;

class RegisterRecordedEventService
{
    public function __construct(private RecordedEventRepository $recordedEventRepository)
    {
    }

    public function __invoke(RegisterRecordedEventRequest $request): RegisterRecordedEventResponse
    {
        $recordedEvent = new RecordedEvent(
            userId: $request->userId,
            event: $request->event
        );

        $this->recordedEventRepository->add($recordedEvent);

        return new RegisterRecordedEventResponse($recordedEvent->id(), $recordedEvent->userId());
    }
}