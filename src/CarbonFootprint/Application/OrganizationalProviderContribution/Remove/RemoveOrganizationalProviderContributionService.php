<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Remove;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveOrganizationalProviderContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
        private OrganizationalProviderContributionAuthorizer $authorizer,
        private OrganizationalCarbonFootprintLocator $locator,
    ) {}

    public function __invoke(RemoveOrganizationalProviderContributionRequest $request, User $user): RemoveOrganizationalProviderContributionResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::Remove, $user);

        $located = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $providerId = $contribution->providerId();

        if ($providerId instanceof ProviderId) {
            $located->carbonFootprint->removeActivitiesOfProvider($providerId);
            $this->locator->save($located);
        }

        $contribution->remove();

        $this->contributionRepository->add($contribution);

        return new RemoveOrganizationalProviderContributionResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveOrganizationalProviderContributionRequest;
    }
}
