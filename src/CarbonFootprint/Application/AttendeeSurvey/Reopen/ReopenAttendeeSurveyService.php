<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Reopen;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReopenAttendeeSurveyService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private AttendeeSurveyRepository $attendeeSurveyRepository,
    ) {}

    public function __invoke(ReopenAttendeeSurveyRequest $request, User $user): void
    {
        $attendeeSurvey = $this->attendeeSurveyRepository->findByEventId($request->eventId);

        if (null === $attendeeSurvey) {
            return;
        }

        $attendeeSurvey->reopen();

        $this->attendeeSurveyRepository->add($attendeeSurvey);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReopenAttendeeSurveyRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReopenAttendeeSurveyRequest;
    }
}
