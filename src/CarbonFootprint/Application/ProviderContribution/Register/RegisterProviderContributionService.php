<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterProviderContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private ProviderRepository $providerRepository,
        private ProviderContributionRepository $providerContributionRepository,
    ) {}

    public function __invoke(RegisterProviderContributionRequest $request, User $user): RegisterProviderContributionResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $provider = $this->providerRepository->find($request->providerId);

        if (null == $provider) {
            throw new \InvalidArgumentException('Provider not found');
        }

        $accountId = $eventCarbonFootprint->accountId();

        if (null == $accountId) {
            throw new \InvalidArgumentException('EventCarbonFootprint has no accountId');
        }

        $providerContribution = new ProviderContribution(ProviderContributionId::generate());
        $providerContribution->register(
            eventCarbonFootprintId: $request->eventCarbonFootprintId,
            accountId: $accountId,
            providerId: $request->providerId,
            token: ProviderContribution::generateToken(),
            companyName: $provider->name(),
        );

        $this->providerContributionRepository->add($providerContribution);

        return new RegisterProviderContributionResponse($providerContribution->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterProviderContributionRequest;
    }
}
