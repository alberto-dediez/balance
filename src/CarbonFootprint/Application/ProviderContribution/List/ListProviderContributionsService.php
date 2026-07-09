<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\List;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ListProviderContributionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private ProviderContributionRepository $providerContributionRepository,
    ) {}

    public function __invoke(ListProviderContributionsRequest $request, User $user): ListProviderContributionsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        return new ListProviderContributionsResponse(
            $this->providerContributionRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ListProviderContributionsRequest;
    }
}
