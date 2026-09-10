<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;

interface CreditPurchaseRepository
{
    public function add(CreditPurchase $creditPurchase): void;

    public function find(CreditPurchaseId $creditPurchaseId): ?CreditPurchase;

    public function existsByReference(string $reference, ?CreditPurchaseId $excluding = null): bool;

    public function nextLotNumberFor(string $reference): int;

    /** @return array<CreditPurchase> */
    public function findByFilter(CreditPurchaseFilter $filter): array;

    public function totalResultsByFilter(CreditPurchaseFilter $filter): int;

    /** @return array<CreditBalance> */
    public function balancesByAccount(AccountId $accountId): array;

    public function balanceFor(AccountId $accountId, ProjectId $projectId): CreditBalance;

    public function purchasedTonnesForProject(ProjectId $projectId): float;

    public function latestValidatedFor(AccountId $accountId, ProjectId $projectId): ?CreditPurchase;

    /** @return list<CreditPurchase> */
    public function latestValidatedPurchases(): array;
}
