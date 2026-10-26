<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Reallocate;

use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Security\Domain\Model\Account\AccountId;

final class ReallocateUnderallocatedContributionsService
{
    private const float MIN_ALLOCATABLE_TONNES = 0.000001;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectRepository $projectRepository,
        private CreditReservationLock $creditReservationLock,
    ) {}

    public function __invoke(ReallocateUnderallocatedContributionsRequest $request): ReallocateUnderallocatedContributionsResponse
    {
        $project = $this->projectRepository->find($request->projectId);

        if (null === $project || ProjectType::EmissionCompensation !== $project->projectType()) {
            return new ReallocateUnderallocatedContributionsResponse();
        }

        return $this->creditReservationLock->withLock(
            $request->accountId,
            $request->projectId,
            fn(): ReallocateUnderallocatedContributionsResponse => $this->reallocate($request->accountId, $request->projectId),
        );
    }

    private function reallocate(AccountId $accountId, ProjectId $projectId): ReallocateUnderallocatedContributionsResponse
    {
        $alreadyAllocated = $this->projectContributionRepository->allocatedTonnesByPurchase($accountId, $projectId);

        $covered = 0;
        $reallocatedTonnes = 0.0;

        foreach ($this->underallocatedContributions($accountId, $projectId) as $contribution) {
            $lines = $this->linesFor($contribution, $accountId, $projectId, $alreadyAllocated);

            if ([] === $lines) {
                continue;
            }

            $contribution->allocateAdditionalCredits($lines);

            $this->projectContributionRepository->add($contribution);

            ++$covered;
            $reallocatedTonnes += array_sum(array_map(static fn(AllocatedCreditLine $line): float => $line->tonnes, $lines));
        }

        return new ReallocateUnderallocatedContributionsResponse(
            contributionsCovered: $covered,
            reallocatedTonnes: round($reallocatedTonnes, 6),
        );
    }

    /**
     * @param array<string, float> $alreadyAllocated
     *
     * @return list<AllocatedCreditLine>
     */
    private function linesFor(
        ProjectContribution $contribution,
        AccountId $accountId,
        ProjectId $projectId,
        array &$alreadyAllocated,
    ): array {
        $pending = $contribution->unallocatedTonnes();

        if ($pending <= self::MIN_ALLOCATABLE_TONNES) {
            return [];
        }

        $lines = [];
        $position = $contribution->nextAllocationPosition();

        foreach ($this->validatedPurchases($accountId, $projectId) as $creditPurchase) {
            if ($pending <= self::MIN_ALLOCATABLE_TONNES) {
                break;
            }

            $allocatedSoFar = $alreadyAllocated[(string) $creditPurchase->id()] ?? 0.0;
            $startTonnes = $creditPurchase->offsetTonnes() + $allocatedSoFar;
            $available = $creditPurchase->availableTonnes() - $allocatedSoFar;

            if ($available <= self::MIN_ALLOCATABLE_TONNES) {
                continue;
            }

            $consumed = min($available, $pending);

            $lines[] = new AllocatedCreditLine(
                position: $position,
                creditPurchaseId: $creditPurchase->id(),
                purchaseReference: $creditPurchase->reference(),
                tonnes: $consumed,
                startTonnes: $startTonnes,
            );

            $alreadyAllocated[(string) $creditPurchase->id()] = $allocatedSoFar + $consumed;
            $pending -= $consumed;
            ++$position;
        }

        return $lines;
    }

    /** @return list<ProjectContribution> */
    private function underallocatedContributions(AccountId $accountId, ProjectId $projectId): array
    {
        $contributions = array_values($this->projectContributionRepository->findByFilter(new ProjectContributionFilter(
            accountId: $accountId,
            projectId: $projectId,
            status: ProjectContributionStatus::Paid,
            limit: 0,
        )));

        $underallocated = array_values(array_filter(
            $contributions,
            static fn(ProjectContribution $contribution): bool => !$contribution->funding()->isImported()
                && $contribution->unallocatedTonnes() > self::MIN_ALLOCATABLE_TONNES,
        ));

        usort(
            $underallocated,
            static fn(ProjectContribution $a, ProjectContribution $b): int => ($a->paidAt()?->getTimestamp() ?? 0) <=> ($b->paidAt()?->getTimestamp() ?? 0),
        );

        return $underallocated;
    }

    /** @return list<CreditPurchase> */
    private function validatedPurchases(AccountId $accountId, ProjectId $projectId): array
    {
        $purchases = array_values($this->creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(
            accountId: $accountId,
            projectId: $projectId,
            status: CreditPurchaseStatus::Validated,
            limit: 0,
        )));

        usort(
            $purchases,
            static fn(CreditPurchase $a, CreditPurchase $b): int => ($a->resolvedAt()?->getTimestamp() ?? 0) <=> ($b->resolvedAt()?->getTimestamp() ?? 0),
        );

        return $purchases;
    }
}
