<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\AssistantUsageRow;
use App\Security\Domain\Model\User\UserId;

interface ActionPlanRepository
{
    public function find(ActionPlanId $id): ?ActionPlan;

    /** @throws ActionPlanConcurrentlyModifiedException */
    public function add(ActionPlan $plan): void;

    public function statusOf(ActionPlanId $id): ?ActionPlanStatus;

    public function remove(ActionPlan $plan): void;

    /** @return list<ActionPlan> */
    public function findByFilter(ActionPlanFilter $filter): array;

    public function totalResultsByFilter(ActionPlanFilter $filter): int;

    public function costOfUserSince(UserId $userId, \DateTimeImmutable $since): string;

    /** @return list<AssistantUsageRow> */
    public function usageRowsBetween(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array;
}
