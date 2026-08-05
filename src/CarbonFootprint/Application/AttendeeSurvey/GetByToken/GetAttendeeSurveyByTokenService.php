<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetByToken;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Shared\Domain\Model\Geo\PlaceFinder;

final class GetAttendeeSurveyByTokenService
{
    public function __construct(
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private EventRepository $eventRepository,
        private MobilityRepository $mobilityRepository,
        private EventVenueLocator $eventVenueLocator,
        private PlaceFinder $placeFinder,
        private int $maxLegs = 4,
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

        return new GetAttendeeSurveyByTokenResponse(
            attendeeSurvey: $attendeeSurvey,
            event: $event,
            mobilities: $mobilities,
            geoEnabled: $this->placeFinder->isOperative() && null !== $this->eventVenueLocator->locate($event),
            maxLegs: $this->maxLegs,
        );
    }
}
