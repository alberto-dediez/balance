<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetPhaseService
{
    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private StageSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetPhaseRequest $request, User $user): GetPhaseResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $stageSections = $this->sectionsBuilder->build($eventCarbonFootprint, $request->phase);

        return new GetPhaseResponse($stageSections->sections, $stageSections->exclusions);
    }
}
