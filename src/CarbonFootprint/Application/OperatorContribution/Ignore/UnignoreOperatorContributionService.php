<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Ignore;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UnignoreOperatorContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OperatorContributionRepository $contributionRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
        private OperatorContributionAuthorizer $authorizer,
    ) {}

    public function __invoke(UnignoreOperatorContributionRequest $request, User $user): UnignoreOperatorContributionResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);
        $companyCarbonFootprintId = $contribution?->companyCarbonFootprintId();
        $companyCarbonFootprint = $companyCarbonFootprintId instanceof CompanyCarbonFootprintId ? $this->companyCarbonFootprintRepository->find($companyCarbonFootprintId) : null;

        if (null === $contribution || null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('OperatorContribution not found');
        }

        $this->companyCarbonFootprintAuthorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);
        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::Edit, $user);

        if ($companyCarbonFootprint->isCompleted()) {
            throw new OperatorContributionClosedException();
        }

        $contribution->unignore();

        $this->contributionRepository->add($contribution);

        return new UnignoreOperatorContributionResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UnignoreOperatorContributionRequest;
    }
}
