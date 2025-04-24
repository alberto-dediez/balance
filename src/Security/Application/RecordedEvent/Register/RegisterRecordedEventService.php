<?php

namespace App\Security\Application\RecordedEvent\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Application\RecordedEvent\RecordedEventAuthorizer;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;

class RegisterRecordedEventService
{
    public function __construct(private RecordedEventRepository $recordedEventRepository, private RecordedEventAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterRecordedEventRequest $request, ?User $performBy): RegisterRecordedEventResponse
    {
        $recordedEvent = new RecordedEvent(
            userId: $request->userId,
            event: $request->event
        );

        if (null == $performBy) {
            $performBy = UserMother::systemUser();
        }

        $this->authorizer->denyUnlessCanDoThat($recordedEvent, Action::Register, $performBy);

        $this->recordedEventRepository->add($recordedEvent);

        return new RegisterRecordedEventResponse($recordedEvent->id(), $recordedEvent->userId());
    }
}