<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Options;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;

/**
 * Projects a guest may contribute to: the ones the owning account adhered to, priced,
 * and — for offsetting projects — backed by enough credit left.
 */
final class ListContributionOptionsService
{
    public function __construct(
        private AccountProjectRepository $accountProjectRepository,
        private ProjectRepository $projectRepository,
        private CreditAvailability $creditAvailability,
    ) {}

    public function __invoke(ListContributionOptionsRequest $request): ListContributionOptionsResponse
    {
        $now = $request->now ?? new \DateTimeImmutable('NOW');
        $options = [];

        $accountProjects = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, limit: 0),
        );

        foreach ($accountProjects as $accountProject) {
            $projectId = $accountProject->projectId();

            if (!$projectId instanceof ProjectId) {
                continue;
            }

            $project = $this->projectRepository->find($projectId);

            if (!$project instanceof Project || $project->isRemoved()) {
                continue;
            }

            $pricePerTonne = $project->pricePerTonne();
            $costPerTonne = $project->costPerTonne();

            if (null === $pricePerTonne || $pricePerTonne <= 0.0 || null === $costPerTonne) {
                continue;
            }

            $limitedByCredit = ProjectType::EmissionCompensation === $project->projectType();
            $availableTonnes = \INF;

            if ($limitedByCredit) {
                $availableTonnes = $this->creditAvailability
                    ->for($request->accountId, $projectId, $now)
                    ->availableTonnes()
                ;

                if ($availableTonnes <= 0.0 || !$this->hasEnoughCredit($availableTonnes, $request->co2eqKg)) {
                    continue;
                }
            }

            $options[] = new ContributionOption(
                project: $project,
                pricePerTonne: $pricePerTonne,
                costPerTonne: $costPerTonne,
                availableTonnes: $availableTonnes,
                limitedByCredit: $limitedByCredit,
                suggestedAmount: null !== $request->co2eqKg
                    ? round($request->co2eqKg / 1000 * $pricePerTonne, 2)
                    : null,
            );
        }

        return new ListContributionOptionsResponse($options);
    }

    private function hasEnoughCredit(float $availableTonnes, ?float $co2eqKg): bool
    {
        return null === $co2eqKg || $availableTonnes >= $co2eqKg / 1000;
    }
}
