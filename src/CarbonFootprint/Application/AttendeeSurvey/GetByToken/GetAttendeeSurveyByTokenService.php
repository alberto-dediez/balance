<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetByToken;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\EventOperations\Domain\Model\Event\EventRepository;

final class GetAttendeeSurveyByTokenService
{
    public function __construct(
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private EventRepository $eventRepository,
        private MobilityRepository $mobilityRepository,
    ) {}

    public function __invoke(GetAttendeeSurveyByTokenRequest $request): GetAttendeeSurveyByTokenResponse
    {
        $attendeeSurvey = $this->attendeeSurveyRepository->findByToken($request->token);

        if (null === $attendeeSurvey) {
            throw new AttendeeSurveyTokenInvalidException();
        }

        $eventId = $attendeeSurvey->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;

        if (null === $event) {
            throw new AttendeeSurveyTokenInvalidException();
        }

        $mobilities = $this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0));

        return new GetAttendeeSurveyByTokenResponse($attendeeSurvey, $event, $mobilities);
    }
}
