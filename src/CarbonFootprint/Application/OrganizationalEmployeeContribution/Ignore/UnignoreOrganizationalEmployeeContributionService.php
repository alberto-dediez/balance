<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthorizer;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UnignoreOrganizationalEmployeeContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private OrganizationalEmployeeContributionAuthorizer $authorizer,
        private OrganizationalCarbonFootprintLocator $locator,
    ) {}

    public function __invoke(UnignoreOrganizationalEmployeeContributionRequest $request, User $user): UnignoreOrganizationalEmployeeContributionResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalEmployeeContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::Edit, $user);

        $located = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $contribution->unignore();

        $this->contributionRepository->add($contribution);

        return new UnignoreOrganizationalEmployeeContributionResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UnignoreOrganizationalEmployeeContributionRequest;
    }
}
