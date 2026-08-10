<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

/** Safety net for the deferred per-contribution check: sweeps every reservation already past its window. */
class ExpirePendingContributionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private ExpireProjectContributionService $expireProjectContribution,
    ) {}

    public function __invoke(ExpirePendingContributionsRequest $request, User $user): ExpirePendingContributionsResponse
    {
        $pending = $this->projectContributionRepository->findByFilter(new ProjectContributionFilter(
            status: ProjectContributionStatus::Pending,
            expiresBefore: $request->now,
            limit: 0,
        ));

        $expired = 0;
        $confirmed = 0;

        foreach ($pending as $projectContribution) {
            $response = $this->expireProjectContribution->__invoke(
                new ExpireProjectContributionRequest($projectContribution->id(), $request->now),
            );

            if (!$response->changed) {
                continue;
            }

            if (ProjectContributionStatus::Paid === $response->status) {
                ++$confirmed;

                continue;
            }

            ++$expired;
        }

        return new ExpirePendingContributionsResponse(expired: $expired, confirmed: $confirmed);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExpirePendingContributionsRequest;
    }
}
