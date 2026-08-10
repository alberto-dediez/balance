<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Fail;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;

final class FailProjectContributionService
{
    public function __construct(private ProjectContributionRepository $projectContributionRepository) {}

    public function __invoke(FailProjectContributionRequest $request): FailProjectContributionResponse
    {
        $projectContribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $projectContribution) {
            throw new \InvalidArgumentException('Project contribution not found');
        }

        if (!$projectContribution->isPending()) {
            return new FailProjectContributionResponse(status: $projectContribution->status(), changed: false);
        }

        $projectContribution->fail($request->reason);
        $this->projectContributionRepository->add($projectContribution);

        return new FailProjectContributionResponse(status: $projectContribution->status(), changed: true);
    }
}
