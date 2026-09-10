<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCreditPurchaseRepository implements CreditPurchaseRepository
{
    /** @param array<string, CreditPurchase> $creditPurchases */
    public function __construct(
        private array $creditPurchases = [],
    ) {}

    public function add(CreditPurchase $creditPurchase): void
    {
        $this->creditPurchases[(string) $creditPurchase->id()] = $creditPurchase;
    }

    public function find(CreditPurchaseId $creditPurchaseId): ?CreditPurchase
    {
        return $this->creditPurchases[(string) $creditPurchaseId] ?? null;
    }

    public function existsByReference(string $reference, ?CreditPurchaseId $excluding = null): bool
    {
        foreach ($this->creditPurchases as $creditPurchase) {
            if ($creditPurchase->reference() !== $reference) {
                continue;
            }

            if ($excluding instanceof CreditPurchaseId && $excluding->equals($creditPurchase->id())) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function nextLotNumberFor(string $reference): int
    {
        $maxLotNumber = 0;

        foreach ($this->creditPurchases as $creditPurchase) {
            if ($creditPurchase->reference() !== $reference) {
                continue;
            }

            $maxLotNumber = max($maxLotNumber, $creditPurchase->lotNumber());
        }

        return $maxLotNumber + 1;
    }

    private function matches(CreditPurchase $creditPurchase, CreditPurchaseFilter $filter): bool
    {
        if (
            null !== $filter->accountId
            && (null === $creditPurchase->accountId() || !$filter->accountId->equals($creditPurchase->accountId()))
        ) {
            return false;
        }

        if (
            null !== $filter->projectId
            && (null === $creditPurchase->projectId() || !$filter->projectId->equals($creditPurchase->projectId()))
        ) {
            return false;
        }

        if (null !== $filter->status && $filter->status !== $creditPurchase->status()) {
            return false;
        }

        if (null !== $filter->paymentType && $filter->paymentType !== $creditPurchase->paymentType()) {
            return false;
        }

        if (
            !empty($filter->reference)
            && !str_contains(strtolower((string) $creditPurchase->reference()), strtolower($filter->reference))
        ) {
            return false;
        }

        return true;
    }

    /** @return array<int, CreditPurchase> */
    public function findByFilter(CreditPurchaseFilter $filter): array
    {
        $results = [];

        if (0 === count($this->creditPurchases)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->creditPurchases as $creditPurchase) {
            if (!$this->matches($creditPurchase, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $creditPurchase;
        }

        return $results;
    }

    public function totalResultsByFilter(CreditPurchaseFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    /** @return array<CreditBalance> */
    public function balancesByAccount(AccountId $accountId): array
    {
        $balances = [];

        $validated = $this->findByFilter(
            new CreditPurchaseFilter(accountId: $accountId, status: CreditPurchaseStatus::Validated, limit: 0),
        );

        foreach ($validated as $creditPurchase) {
            $projectId = $creditPurchase->projectId();

            if (null === $projectId) {
                continue;
            }

            $key = (string) $projectId;

            if (!isset($balances[$key])) {
                $balances[$key] = [
                    'projectId' => $projectId,
                    'purchasedTonnes' => 0.0,
                    'purchasesCount' => 0,
                ];
            }

            $balances[$key]['purchasedTonnes'] += $creditPurchase->availableTonnes();
            ++$balances[$key]['purchasesCount'];
        }

        return array_map(
            static fn(array $balance): CreditBalance => new CreditBalance(
                projectId: $balance['projectId'],
                purchasedTonnes: $balance['purchasedTonnes'],
                consumedTonnes: 0.0,
                purchasesCount: $balance['purchasesCount'],
            ),
            array_values($balances),
        );
    }

    public function balanceFor(AccountId $accountId, ProjectId $projectId): CreditBalance
    {
        foreach ($this->balancesByAccount($accountId) as $balance) {
            if ($balance->projectId()->equals($projectId)) {
                return $balance;
            }
        }

        return new CreditBalance(
            projectId: $projectId,
            purchasedTonnes: 0.0,
            consumedTonnes: 0.0,
            purchasesCount: 0,
        );
    }

    public function purchasedTonnesForProject(ProjectId $projectId): float
    {
        $purchased = 0.0;

        $validated = $this->findByFilter(
            new CreditPurchaseFilter(projectId: $projectId, status: CreditPurchaseStatus::Validated, limit: 0),
        );

        foreach ($validated as $creditPurchase) {
            $purchased += $creditPurchase->availableTonnes();
        }

        return $purchased;
    }

    public function latestValidatedFor(AccountId $accountId, ProjectId $projectId): ?CreditPurchase
    {
        $validated = $this->validatedPurchases(new CreditPurchaseFilter(
            accountId: $accountId,
            projectId: $projectId,
            status: CreditPurchaseStatus::Validated,
            limit: 0,
        ));

        return array_pop($validated);
    }

    /** @return list<CreditPurchase> */
    public function latestValidatedPurchases(): array
    {
        $latest = [];

        foreach ($this->validatedPurchases(new CreditPurchaseFilter(status: CreditPurchaseStatus::Validated, limit: 0)) as $creditPurchase) {
            $accountId = $creditPurchase->accountId();
            $projectId = $creditPurchase->projectId();

            if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
                continue;
            }

            $latest[(string) $accountId . '|' . (string) $projectId] = $creditPurchase;
        }

        return array_values($latest);
    }

    /** @return list<CreditPurchase> */
    private function validatedPurchases(CreditPurchaseFilter $filter): array
    {
        $purchases = array_values($this->findByFilter($filter));

        usort(
            $purchases,
            static fn(CreditPurchase $a, CreditPurchase $b): int => [$a->resolvedAt()?->getTimestamp() ?? 0, (string) $a->id()]
                <=> [$b->resolvedAt()?->getTimestamp() ?? 0, (string) $b->id()],
        );

        return $purchases;
    }
}
