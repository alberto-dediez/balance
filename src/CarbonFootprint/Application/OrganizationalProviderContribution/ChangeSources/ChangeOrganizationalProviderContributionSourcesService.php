<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ChangeOrganizationalProviderContributionSourcesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
        private OrganizationalProviderContributionAuthorizer $authorizer,
        private OrganizationalCarbonFootprintLocator $locator,
    ) {}

    public function __invoke(ChangeOrganizationalProviderContributionSourcesRequest $request, User $user): ChangeOrganizationalProviderContributionSourcesResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::Edit, $user);

        $located = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $providerId = $contribution->providerId();

        if (!$providerId instanceof ProviderId) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution has no provider');
        }

        $previousSources = $contribution->sources();

        $contribution->changeSources($request->sources);

        foreach ($previousSources as $source) {
            if ($contribution->covers($source)) {
                continue;
            }

            $located->carbonFootprint->retainActivities(
                new ActivityPlacement(
                    stage: Scope::Scope3,
                    source: $source,
                    providerId: $providerId,
                    propertyId: $contribution->propertyId(),
                ),
                [],
            );
        }

        $this->locator->save($located);
        $this->contributionRepository->add($contribution);

        return new ChangeOrganizationalProviderContributionSourcesResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ChangeOrganizationalProviderContributionSourcesRequest;
    }
}
