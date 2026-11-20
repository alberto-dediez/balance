<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Options;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\Balance\CreditSource;
use App\Balance\Application\CreditPurchase\Balance\CreditSourceSelector;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;

final class ListContributionOptionsService
{
    public function __construct(
        private AccountProjectRepository $accountProjectRepository,
        private ProjectRepository $projectRepository,
        private CreditAvailability $creditAvailability,
        private AccountFeaturesPolicy $accountFeaturesPolicy,
        private CreditSourceSelector $creditSourceSelector,
    ) {}

    public function __invoke(ListContributionOptionsRequest $request): ListContributionOptionsResponse
    {
        if (!$this->accountFeaturesPolicy->isEnabled(AccountFeature::Compensation, $request->accountId)) {
            return new ListContributionOptionsResponse([], compensationEnabled: false);
        }

        $now = $request->now ?? new \DateTimeImmutable('NOW');
        $options = [];

        foreach ($this->projectIds($request, $now) as ['projectId' => $projectId, 'adhered' => $adhered]) {
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

            if (!$limitedByCredit && !$adhered) {
                continue;
            }

            $availableTonnes = \INF;
            $ownAvailableTonnes = 0.0;
            $funding = ProjectContributionFunding::Payment;

            if ($limitedByCredit && $request->ownerMode) {
                $availableTonnes = $this->creditAvailability
                    ->for($request->accountId, $projectId, $now)
                    ->availableTonnes()
                ;

                if ($availableTonnes <= 0.0) {
                    continue;
                }

                $ownAvailableTonnes = $this->creditAvailability->ownFor($request->accountId, $projectId, $now);

                if ($ownAvailableTonnes > 0.0) {
                    $funding = ProjectContributionFunding::OwnCredit;
                    $availableTonnes = $ownAvailableTonnes;
                }
            } elseif ($limitedByCredit) {
                $source = $this->creditSourceSelector->select(
                    accountId: $request->accountId,
                    projectId: $projectId,
                    allowShared: $request->allowSharedCredit,
                    propertyId: $request->propertyId,
                    tonnes: null !== $request->co2eqKg ? $request->co2eqKg / 1000 : null,
                    now: $now,
                );

                if (!$source instanceof CreditSource) {
                    continue;
                }

                $availableTonnes = $source->availableTonnes;
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
                funding: $funding,
                ownAvailableTonnes: $ownAvailableTonnes,
            );
        }

        return new ListContributionOptionsResponse($options);
    }

    /** @return array<string, array{projectId: ProjectId, adhered: bool}> */
    private function projectIds(ListContributionOptionsRequest $request, \DateTimeImmutable $now): array
    {
        $projectIds = [];

        $accountProjects = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, limit: 0),
        );

        foreach ($accountProjects as $accountProject) {
            $projectId = $accountProject->projectId();

            if ($projectId instanceof ProjectId) {
                $projectIds[(string) $projectId] = ['projectId' => $projectId, 'adhered' => true];
            }
        }

        if ($request->ownerMode || !$request->allowSharedCredit) {
            return $projectIds;
        }

        foreach ($this->creditSourceSelector->sharedProjectIds($request->accountId, $request->propertyId, $now) as $projectId) {
            $projectIds[(string) $projectId] ??= ['projectId' => $projectId, 'adhered' => false];
        }

        return $projectIds;
    }
}
