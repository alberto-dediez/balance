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

    /** Tonnes held by paid contributions plus pending ones still within their reservation window. */
    public function committedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float;

    /** @return array<string, float> Committed tonnes per project id, for every project of the account. */
    public function committedTonnesByAccount(AccountId $accountId, \DateTimeImmutable $now): array;

    /** Tonnes held by pending contributions whose reservation window is still open. */
    public function reservedTonnes(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): float;
}
