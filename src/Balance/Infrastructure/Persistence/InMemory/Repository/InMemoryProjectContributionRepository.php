<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProjectContributionRepository implements ProjectContributionRepository
{
    /** @param array<string, ProjectContribution> $projectContributions */
    public function __construct(
        private array $projectContributions = [],
    ) {}

    public function add(ProjectContribution $projectContribution): void
    {
        $this->projectContributions[(string) $projectContribution->id()] = $projectContribution;
    }

    public function find(ProjectContributionId $projectContributionId): ?ProjectContribution
    {
        return $this->projectContributions[(string) $projectContributionId] ?? null;
    }

    public function findOneByPaymentId(PaymentId $paymentId): ?ProjectContribution
    {
        foreach ($this->projectContributions as $projectContribution) {
            $contributionPaymentId = $projectContribution->paymentId();

            if ($contributionPaymentId instanceof PaymentId && $contributionPaymentId->equals($paymentId)) {
                return $projectContribution;
            }
        }

        return null;
    }

    private function matches(ProjectContribution $projectContribution, ProjectContributionFilter $filter): bool
    {
        $accountId = $projectContribution->accountId();
        $projectId = $projectContribution->projectId();
        $paymentId = $projectContribution->paymentId();
        $expiresAt = $projectContribution->expiresAt();

        if (null !== $filter->accountId && (null === $accountId || !$filter->accountId->equals($accountId))) {
            return false;
        }

        if (null !== $filter->projectId && (null === $projectId || !$filter->projectId->equals($projectId))) {
            return false;
        }

        if (null !== $filter->status && $filter->status !== $projectContribution->status()) {
            return false;
        }

        if (null !== $filter->origin && $filter->origin !== $projectContribution->origin()) {
            return false;
        }

        if (null !== $filter->funding && $filter->funding !== $projectContribution->funding()) {
            return false;
        }

        if (null !== $filter->originId && $filter->originId !== $projectContribution->originId()) {
            return false;
        }

        if (null !== $filter->originIds && !in_array($projectContribution->originId(), $filter->originIds, true)) {
            return false;
        }

        if (null !== $filter->paymentId && (null === $paymentId || !$filter->paymentId->equals($paymentId))) {
            return false;
        }

        if ($filter->from instanceof \DateTimeImmutable && $projectContribution->registeredAt() < $filter->from) {
            return false;
        }

        if ($filter->to instanceof \DateTimeImmutable && $projectContribution->registeredAt() > $filter->to) {
            return false;
        }

        return !($filter->expiresBefore instanceof \DateTimeImmutable)
            || (null !== $expiresAt && $expiresAt <= $filter->expiresBefore);
    }

    /** @return array<int, ProjectContribution> */
    public function findByFilter(ProjectContributionFilter $filter): array
    {
        $results = [];
        $offset = $filter->page * $filter->limit;

        foreach ($this->projectContributions as $projectContribution) {
            if (!$this->matches($projectContribution, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $projectContribution;
        }

        return $results;
    }

    public function totalResultsByFilter(ProjectContributionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    public function committedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float
    {
        return $this->committedTonnesByAccount($accountId, $now)[(string) $projectId] ?? 0.0;
    }

    /** @return array<string, float> */
    public function committedTonnesByAccount(AccountId $accountId, \DateTimeImmutable $now): array
    {
        $committed = [];

        foreach ($this->findByFilter(new ProjectContributionFilter(accountId: $accountId, limit: 0)) as $projectContribution) {
            $projectId = $projectContribution->projectId();

            if (!$projectId instanceof ProjectId || !$projectContribution->reservesCredit($now)) {
                continue;
            }

            $key = (string) $projectId;
            $committed[$key] = ($committed[$key] ?? 0.0) + $projectContribution->tonnes();
        }

        return $committed;
    }

    /** @return array<string, float> */
    public function allocatedTonnesByPurchase(AccountId $accountId, ProjectId $projectId): array
    {
        $allocated = [];

        $contributions = $this->findByFilter(new ProjectContributionFilter(
            accountId: $accountId,
            projectId: $projectId,
            limit: 0,
        ));

        foreach ($contributions as $projectContribution) {
            foreach ($projectContribution->creditAllocations() as $allocation) {
                $key = (string) $allocation->creditPurchaseId();
                $allocated[$key] = ($allocated[$key] ?? 0.0) + $allocation->tonnes();
            }
        }

        return $allocated;
    }

    /** @return list<ProjectContributionCreditAllocation> */
    public function findAllocationsByCreditPurchase(CreditPurchaseId $creditPurchaseId): array
    {
        $allocations = [];

        foreach ($this->projectContributions as $projectContribution) {
            foreach ($projectContribution->creditAllocations() as $allocation) {
                if ($allocation->creditPurchaseId()->equals($creditPurchaseId)) {
                    $allocations[] = $allocation;
                }
            }
        }

        usort(
            $allocations,
            static fn(ProjectContributionCreditAllocation $a, ProjectContributionCreditAllocation $b): int => $a->startTonnes() <=> $b->startTonnes(),
        );

        return $allocations;
    }

    /** @return array<string, list<ProjectContributionCreditAllocation>> */
    public function findAllocationsByAccount(AccountId $accountId): array
    {
        $allocations = [];

        foreach ($this->findByFilter(new ProjectContributionFilter(accountId: $accountId, limit: 0)) as $projectContribution) {
            foreach ($projectContribution->creditAllocations() as $allocation) {
                $allocations[(string) $allocation->creditPurchaseId()][] = $allocation;
            }
        }

        foreach ($allocations as $creditPurchaseId => $purchaseAllocations) {
            usort(
                $purchaseAllocations,
                static fn(ProjectContributionCreditAllocation $a, ProjectContributionCreditAllocation $b): int => $a->startTonnes() <=> $b->startTonnes(),
            );

            $allocations[$creditPurchaseId] = $purchaseAllocations;
        }

        return $allocations;
    }

    public function committedTonnesForProject(ProjectId $projectId, \DateTimeImmutable $now): float
    {
        $committed = 0.0;

        foreach ($this->findByFilter(new ProjectContributionFilter(projectId: $projectId, limit: 0)) as $projectContribution) {
            if ($projectContribution->reservesCredit($now)) {
                $committed += $projectContribution->tonnes();
            }
        }

        return $committed;
    }

    public function reservedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float
    {
        $reserved = 0.0;

        $pending = $this->findByFilter(new ProjectContributionFilter(
            accountId: $accountId,
            projectId: $projectId,
            status: ProjectContributionStatus::Pending,
            limit: 0,
        ));

        foreach ($pending as $projectContribution) {
            if ($projectContribution->reservesCredit($now)) {
                $reserved += $projectContribution->tonnes();
            }
        }

        return $reserved;
    }
}
