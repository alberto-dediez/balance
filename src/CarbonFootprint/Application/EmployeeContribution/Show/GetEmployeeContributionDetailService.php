<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Show;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GetEmployeeContributionDetailService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmployeeContributionRepository $employeeContributionRepository,
        private EmployeeContributionAuthorizer $authorizer,
    ) {}

    public function __invoke(GetEmployeeContributionDetailRequest $request, User $user): GetEmployeeContributionDetailResponse
    {
        $employeeContribution = $this->employeeContributionRepository->find($request->employeeContributionId);

        if (null == $employeeContribution) {
            throw new \InvalidArgumentException('EmployeeContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($employeeContribution, SecurityAction::View, $user);

        return new GetEmployeeContributionDetailResponse($employeeContribution);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetEmployeeContributionDetailRequest;
    }
}
