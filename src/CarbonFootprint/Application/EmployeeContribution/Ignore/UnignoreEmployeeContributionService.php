<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Ignore;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UnignoreEmployeeContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmployeeContributionRepository $employeeContributionRepository,
        private EmployeeContributionAuthorizer $authorizer,
    ) {}

    public function __invoke(UnignoreEmployeeContributionRequest $request, User $user): UnignoreEmployeeContributionResponse
    {
        $employeeContribution = $this->employeeContributionRepository->find($request->employeeContributionId);

        if (null == $employeeContribution) {
            throw new \InvalidArgumentException('EmployeeContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($employeeContribution, SecurityAction::Edit, $user);

        $employeeContribution->unignore();

        $this->employeeContributionRepository->add($employeeContribution);

        return new UnignoreEmployeeContributionResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UnignoreEmployeeContributionRequest;
    }
}
