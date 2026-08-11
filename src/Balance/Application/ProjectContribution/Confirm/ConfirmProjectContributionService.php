<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Confirm;

use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;

final class ConfirmProjectContributionService
{
    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private AllocateContributionCreditsService $allocateContributionCredits,
    ) {}

    public function __invoke(ConfirmProjectContributionRequest $request): ConfirmProjectContributionResponse
    {
        $projectContribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $projectContribution) {
            throw new \InvalidArgumentException('Project contribution not found');
        }

        $wasPending = $projectContribution->isPending();

        // Even when there is nothing to change the aggregate is told about the payment, so that a
        // confirmation landing on an expired or failed contribution is reported instead of lost.
        $projectContribution->pay();

        if (!$wasPending) {
            return new ConfirmProjectContributionResponse(status: $projectContribution->status(), changed: false);
        }

        $this->projectContributionRepository->add($projectContribution);

        $this->allocateContributionCredits->__invoke(
            new AllocateContributionCreditsRequest($projectContribution->id()),
        );

        return new ConfirmProjectContributionResponse(status: $projectContribution->status(), changed: true);
    }
}
