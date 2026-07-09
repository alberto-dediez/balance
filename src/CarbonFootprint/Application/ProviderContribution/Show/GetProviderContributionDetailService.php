<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Show;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionPhaseSectionsBuilder;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GetProviderContributionDetailService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProviderContributionRepository $providerContributionRepository,
        private ProviderContributionAuthorizer $authorizer,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private ProviderContributionPhaseSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetProviderContributionDetailRequest $request, User $user): GetProviderContributionDetailResponse
    {
        $providerContribution = $this->providerContributionRepository->find($request->providerContributionId);

        if (null == $providerContribution) {
            throw new \InvalidArgumentException('ProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($providerContribution, SecurityAction::View, $user);

        $eventCarbonFootprintId = $providerContribution->eventCarbonFootprintId();
        $providerId = $providerContribution->providerId();

        if (null === $eventCarbonFootprintId || !$providerId instanceof ProviderId) {
            throw new \InvalidArgumentException('ProviderContribution is not fully registered');
        }

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $sectionsByPhase = [];
        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $sectionsByPhase[$phase->value] = $this->sectionsBuilder->build($eventCarbonFootprint, $phase, $providerId);
        }

        return new GetProviderContributionDetailResponse($providerContribution, $sectionsByPhase);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetProviderContributionDetailRequest;
    }
}
