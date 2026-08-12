<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Security\Domain\Model\Account\AccountId;

interface ProjectContributionRepository
{
    public function add(ProjectContribution $projectContribution): void;

    public function find(ProjectContributionId $projectContributionId): ?ProjectContribution;

    public function findOneByPaymentId(PaymentId $paymentId): ?ProjectContribution;

    /** @return array<ProjectContribution> */
    public function findByFilter(ProjectContributionFilter $filter): array;

    public function totalResultsByFilter(ProjectContributionFilter $filter): int;

    public function committedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float;

    /** @return array<string, float> */
    public function committedTonnesByAccount(AccountId $accountId, \DateTimeImmutable $now): array;

    public function reservedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float;

    /** @return array<string, float> */
    public function allocatedTonnesByPurchase(AccountId $accountId, ProjectId $projectId): array;
}
