<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Register;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterAttendeeSurveyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private EventRepository $eventRepository,
    ) {}

    public function __invoke(RegisterAttendeeSurveyRequest $request, User $user): RegisterAttendeeSurveyResponse
    {
        $existing = $this->attendeeSurveyRepository->findByEventId($request->eventId);

        if (null !== $existing) {
            return new RegisterAttendeeSurveyResponse($existing);
        }

        $event = $this->eventRepository->find($request->eventId);

        if (null == $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $accountId = $event->accountId();

        if (null == $accountId) {
            throw new \InvalidArgumentException('Event has no accountId');
        }

        $attendeeSurvey = new AttendeeSurvey(AttendeeSurveyId::generate());
        $attendeeSurvey->register(
            eventId: $request->eventId,
            accountId: $accountId,
            token: AttendeeSurvey::generateToken(),
        );

        $this->attendeeSurveyRepository->add($attendeeSurvey);

        return new RegisterAttendeeSurveyResponse($attendeeSurvey);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAttendeeSurveyRequest;
    }
}
