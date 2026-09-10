<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Reallocate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

final class ReallocateAllUnderallocatedContributionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private ReallocateUnderallocatedContributionsService $reallocateUnderallocatedContributions,
    ) {}

    public function __invoke(
        ReallocateAllUnderallocatedContributionsRequest $request,
        User $user,
    ): ReallocateAllUnderallocatedContributionsResponse {
        $pairs = 0;
        $covered = 0;
        $reallocatedTonnes = 0.0;

        foreach ($this->pairsToProcess($request) as [$accountId, $projectId]) {
            $response = $this->reallocateUnderallocatedContributions->__invoke(
                new ReallocateUnderallocatedContributionsRequest(accountId: $accountId, projectId: $projectId),
            );

            ++$pairs;
            $covered += $response->contributionsCovered;
            $reallocatedTonnes += $response->reallocatedTonnes;
        }

        return new ReallocateAllUnderallocatedContributionsResponse(
            pairsProcessed: $pairs,
            contributionsCovered: $covered,
            reallocatedTonnes: round($reallocatedTonnes, 6),
        );
    }

    /** @return list<array{AccountId, ProjectId}> */
    private function pairsToProcess(ReallocateAllUnderallocatedContributionsRequest $request): array
    {
        $pairs = [];

        $contributions = $this->projectContributionRepository->underallocatedContributions(
            $request->accountId,
            $request->projectId,
        );

        foreach ($contributions as $contribution) {
            $accountId = $contribution->accountId();
            $projectId = $contribution->projectId();

            if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
                continue;
            }

            $pairs[(string) $accountId . '|' . (string) $projectId] = [$accountId, $projectId];
        }

        return array_values($pairs);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReallocateAllUnderallocatedContributionsRequest;
    }
}
