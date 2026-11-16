<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Allocate;

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
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;

final class AllocateContributionCreditsService
{
    private const float MIN_ALLOCATABLE_TONNES = 0.000001;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectRepository $projectRepository,
        private CreditReservationLock $creditReservationLock,
    ) {}

    public function __invoke(AllocateContributionCreditsRequest $request): void
    {
        $projectContribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $projectContribution) {
            throw new \InvalidArgumentException('Project contribution not found');
        }

        $creditAccountId = $projectContribution->creditAccountId();
        $projectId = $projectContribution->projectId();

        if (!$creditAccountId instanceof AccountId || !$projectId instanceof ProjectId) {
            return;
        }

        if ($projectContribution->hasCreditAllocations()) {
            return;
        }

        $project = $this->projectRepository->find($projectId);

        if (null === $project || ProjectType::EmissionCompensation !== $project->projectType()) {
            return;
        }

        $this->creditReservationLock->withLock($creditAccountId, $projectId, function () use ($projectContribution, $creditAccountId, $projectId): void {
            $lines = $this->allocate($creditAccountId, $projectId, $projectContribution);

            $projectContribution->allocateCredits($lines);

            $allocatedTonnes = array_sum(array_map(static fn(AllocatedCreditLine $line): float => $line->tonnes, $lines));

            if ($allocatedTonnes + self::MIN_ALLOCATABLE_TONNES < $projectContribution->tonnes()) {
                $projectContribution->reportUnderallocatedCredits($allocatedTonnes);
            }

            $this->projectContributionRepository->add($projectContribution);
        });
    }

    /** @return list<AllocatedCreditLine> */
    private function allocate(AccountId $creditAccountId, ProjectId $projectId, ProjectContribution $projectContribution): array
    {
        $alreadyAllocated = $this->projectContributionRepository->allocatedTonnesByPurchase($creditAccountId, $projectId);

        $lines = [];
        $position = 0;
        $pending = $projectContribution->tonnes();

        foreach ($this->validatedPurchases($creditAccountId, $projectId, $projectContribution) as $creditPurchase) {
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

            $pending -= $consumed;
            ++$position;
        }

        return $lines;
    }

    /** @return list<CreditPurchase> */
    private function validatedPurchases(
        AccountId $creditAccountId,
        ProjectId $projectId,
        ProjectContribution $projectContribution,
    ): array {
        $purchases = $this->creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(
            accountId: $creditAccountId,
            projectId: $projectId,
            status: CreditPurchaseStatus::Validated,
            paymentType: $projectContribution->allowedCreditPaymentType(),
            limit: 0,
        ));

        $purchases = array_values($purchases);

        usort(
            $purchases,
            static fn(CreditPurchase $a, CreditPurchase $b): int => ($a->resolvedAt()?->getTimestamp() ?? 0) <=> ($b->resolvedAt()?->getTimestamp() ?? 0),
        );

        return $purchases;
    }
}
