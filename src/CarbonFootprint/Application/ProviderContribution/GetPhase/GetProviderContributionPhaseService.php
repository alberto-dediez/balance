<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetPhase;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionPhaseSectionsBuilder;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Provider\ProviderId;

final class GetProviderContributionPhaseService
{
    public function __construct(
        private ProviderContributionAuthenticator $authenticator,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private ProviderContributionPhaseSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetProviderContributionPhaseRequest $request): GetProviderContributionPhaseResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $providerContribution = $authentication->providerContribution;

        $eventCarbonFootprintId = $providerContribution->eventCarbonFootprintId();
        $providerId = $providerContribution->providerId();

        if (null === $eventCarbonFootprintId || !$providerId instanceof ProviderId) {
            throw new ProviderContributionTokenInvalidException();
        }

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new ProviderContributionTokenInvalidException();
        }

        $sections = $this->sectionsBuilder->build($eventCarbonFootprint, $request->phase, $providerId);

        return new GetProviderContributionPhaseResponse($providerContribution, $sections);
    }
}
