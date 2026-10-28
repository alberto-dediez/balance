<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class IgnoreOrganizationalProviderContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
        private OrganizationalProviderContributionAuthorizer $authorizer,
        private OrganizationalCarbonFootprintLocator $locator,
    ) {}

    public function __invoke(IgnoreOrganizationalProviderContributionRequest $request, User $user): IgnoreOrganizationalProviderContributionResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::Edit, $user);

        $located = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $contribution->ignore();

        $this->contributionRepository->add($contribution);

        return new IgnoreOrganizationalProviderContributionResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof IgnoreOrganizationalProviderContributionRequest;
    }
}
