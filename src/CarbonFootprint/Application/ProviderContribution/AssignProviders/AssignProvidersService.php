<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AssignProviders;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\Register\RegisterProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Register\RegisterProviderContributionService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssignProvidersService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private ProviderContributionRepository $providerContributionRepository,
        private RegisterProviderContributionService $registerProviderContributionService,
    ) {}

    public function __invoke(AssignProvidersRequest $request, User $user): AssignProvidersResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $existingContributions = $this->providerContributionRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId);

        $requestedProviderIds = array_map(
            static fn(AssignedProvider $assignedProvider) => (string) $assignedProvider->providerId,
            $request->providers,
        );

        foreach ($existingContributions as $existingContribution) {
            $subjectProviderId = $existingContribution->providerId();

            if (null === $subjectProviderId) {
                continue;
            }

            $isStillAssigned = in_array((string) $subjectProviderId, $requestedProviderIds, true);

            if (!$isStillAssigned && !$existingContribution->status()->isIgnored()) {
                $existingContribution->ignore();
                $this->providerContributionRepository->add($existingContribution);
            }

            if ($isStillAssigned && $existingContribution->status()->isIgnored()) {
                $existingContribution->unignore();
                $this->providerContributionRepository->add($existingContribution);
            }
        }

        foreach ($request->providers as $assignedProvider) {
            $alreadyExists = false;

            foreach ($existingContributions as $existingContribution) {
                $subjectProviderId = $existingContribution->providerId();

                if ($subjectProviderId instanceof ProviderId && $subjectProviderId->equals($assignedProvider->providerId)) {
                    $alreadyExists = true;

                    break;
                }
            }

            if ($alreadyExists) {
                continue;
            }

            ($this->registerProviderContributionService)(
                new RegisterProviderContributionRequest(
                    eventCarbonFootprintId: $request->eventCarbonFootprintId,
                    providerId: $assignedProvider->providerId,
                ),
                $user,
            );
        }

        $eventCarbonFootprint->answerProviders();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return new AssignProvidersResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssignProvidersRequest;
    }
}
