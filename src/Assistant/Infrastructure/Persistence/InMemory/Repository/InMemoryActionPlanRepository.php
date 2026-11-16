<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\InMemory\Repository;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\AssistantUsageRowBuilder;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryActionPlanRepository implements ActionPlanRepository
{
    /** @param array<string, ActionPlan> $plans */
    public function __construct(private array $plans = []) {}

    public function find(ActionPlanId $id): ?ActionPlan
    {
        return $this->plans[(string) $id] ?? null;
    }

    public function add(ActionPlan $plan): void
    {
        $this->plans[(string) $plan->id()] = $plan;
    }

    public function statusOf(ActionPlanId $id): ?ActionPlanStatus
    {
        return $this->find($id)?->status();
    }

    public function remove(ActionPlan $plan): void
    {
        unset($this->plans[(string) $plan->id()]);
    }

    public function findByFilter(ActionPlanFilter $filter): array
    {
        $results = array_values(array_filter($this->plans, fn(ActionPlan $plan): bool => $this->matches($plan, $filter)));

        usort($results, static fn(ActionPlan $a, ActionPlan $b): int => 'ASC' === $filter->orderWay
            ? $a->proposedAt() <=> $b->proposedAt()
            : $b->proposedAt() <=> $a->proposedAt());

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(ActionPlanFilter $filter): int
    {
        return count(array_filter($this->plans, fn(ActionPlan $plan): bool => $this->matches($plan, $filter)));
    }

    public function costOfUserSince(UserId $userId, \DateTimeImmutable $since): string
    {
        $cost = 0.0;

        foreach ($this->plans as $plan) {
            if ($plan->isOf($userId) && ($plan->finishedAt() ?? $plan->startedAt() ?? $plan->proposedAt()) >= $since) {
                $cost += (float) $plan->usage()->costUsd();
            }
        }

        return AssistantUsage::formatCost($cost);
    }

    public function usageRowsBetween(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $builder = new AssistantUsageRowBuilder();

        foreach ($this->plans as $plan) {
            if (($from instanceof \DateTimeImmutable && $plan->proposedAt() < $from) || ($to instanceof \DateTimeImmutable && $plan->proposedAt() >= $to)) {
                continue;
            }

            $usage = $plan->usage();
            $builder->add($plan->proposedAt(), $plan->userId(), $usage->model(), $usage->inputTokens(), $usage->outputTokens(), $usage->costUsd());
        }

        return $builder->rows();
    }

    public function clear(): void
    {
        $this->plans = [];
    }

    private function matches(ActionPlan $plan, ActionPlanFilter $filter): bool
    {
        if ($filter->conversationId instanceof ConversationId && !$plan->conversationId()->equals($filter->conversationId)) {
            return false;
        }

        if ($filter->userId instanceof UserId && !$plan->isOf($filter->userId)) {
            return false;
        }

        if (null !== $filter->statuses && [] !== $filter->statuses && !in_array($plan->status(), $filter->statuses, true)) {
            return false;
        }

        if ($filter->proposedBefore instanceof \DateTimeImmutable && $plan->proposedAt() >= $filter->proposedBefore) {
            return false;
        }

        $lastProgressAt = $plan->lastProgressAt() ?? $plan->approvedAt();

        if ($filter->lastProgressBefore instanceof \DateTimeImmutable && (null === $lastProgressAt || $lastProgressAt >= $filter->lastProgressBefore)) {
            return false;
        }

        return true;
    }
}
