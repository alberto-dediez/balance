<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Event\EventRepository;

final class GetProviderContributionGeneralInfoService
{
    public function __construct(
        private ProviderContributionAuthenticator $authenticator,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventRepository $eventRepository,
    ) {}

    public function __invoke(GetProviderContributionGeneralInfoRequest $request): GetProviderContributionGeneralInfoResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $providerContribution = $authentication->providerContribution;

        $eventCarbonFootprintId = $providerContribution->eventCarbonFootprintId();
        $eventCarbonFootprint = null !== $eventCarbonFootprintId ? $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId) : null;
        $eventId = $eventCarbonFootprint?->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;

        if (null === $event) {
            throw new ProviderContributionTokenInvalidException();
        }

        return new GetProviderContributionGeneralInfoResponse($providerContribution, $event);
    }
}
