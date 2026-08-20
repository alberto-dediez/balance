<?php

declare(strict_types=1);

namespace App\Security\Application\RecordedEvent\Register;

use App\Security\Application\Action;
use App\Security\Application\RecordedEvent\RecordedEventAuthorizer;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class RegisterRecordedEventService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private RecordedEventRepository $recordedEventRepository, private RecordedEventAuthorizer $authorizer) {}

    public function __invoke(RegisterRecordedEventRequest $request, ?User $performBy): RegisterRecordedEventResponse
    {
        $recordedEvent = new RecordedEvent(
            userId: $request->userId,
            event: $request->event,
            impersonatorId: $request->impersonatorId,
        );

        if (null == $performBy) {
            $performBy = UserMother::systemUser();
        }

        $this->authorizer->denyUnlessCanDoThat($recordedEvent, Action::Register, $performBy);

        $this->recordedEventRepository->add($recordedEvent);

        return new RegisterRecordedEventResponse($recordedEvent->id(), $recordedEvent->userId(), $recordedEvent->impersonatorId());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterRecordedEventRequest;
    }
}
