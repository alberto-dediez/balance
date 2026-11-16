<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ActionPlanRepositoryTest extends KernelTestCase
{
    public function testAddsFindsAndRemovesAPlan(): void
    {
        $repository = $this->createSUT();
        $plan = ActionPlanMother::proposed(UserMother::adminUser()->id());

        $this->assertNull($repository->find($plan->id()));

        $repository->add($plan);
        $this->clear();

        $this->assertNotNull($repository->find($plan->id()));
        $this->assertSame(ActionPlanStatus::Proposed, $repository->statusOf($plan->id()));
        $this->assertNull($repository->statusOf(ActionPlanId::generate()));

        $repository->remove($plan);
        $this->clear();

        $this->assertNull($repository->find($plan->id()));
    }

    public function testFiltersByConversationAndStatuses(): void
    {
        $repository = $this->createSUT();
        $userId = UserMother::adminUser()->id();
        $conversationId = ConversationId::generate();
        $proposed = ActionPlanMother::proposed($userId, $conversationId);
        $approved = ActionPlanMother::approved($userId, $conversationId);
        $other = ActionPlanMother::proposed($userId);

        foreach ([$proposed, $approved, $other] as $plan) {
            $repository->add($plan);
        }
        $this->clear();

        $this->assertSame([(string) $approved->id()], $this->ids($repository->findByFilter(new ActionPlanFilter(conversationId: $conversationId, statuses: ActionPlanStatus::active(), limit: 0))));
        $this->assertSame(2, $repository->totalResultsByFilter(new ActionPlanFilter(conversationId: $conversationId, limit: 0)));
        $this->assertSame(2, $repository->totalResultsByFilter(new ActionPlanFilter(statuses: [ActionPlanStatus::Proposed], limit: 0)));
    }

    public function testFiltersTheActivePlansWithoutRecentProgress(): void
    {
        $repository = $this->createSUT();
        $userId = UserMother::adminUser()->id();
        $longAgo = new \DateTimeImmutable('2026-11-11 09:00:00');
        $idle = ActionPlanMother::executing($userId, now: $longAgo);
        $neverStarted = ActionPlanMother::approved($userId, now: $longAgo);
        $progressing = ActionPlanMother::executing($userId, now: $longAgo);
        $progressing->completeStep(1, 'Hecho', [], null, null, [], new \DateTimeImmutable('2026-11-11 09:58:00'));

        foreach ([$idle, $neverStarted, $progressing] as $plan) {
            $repository->add($plan);
        }
        $this->clear();

        $stale = $repository->findByFilter(new ActionPlanFilter(statuses: ActionPlanStatus::active(), lastProgressBefore: new \DateTimeImmutable('2026-11-11 09:50:00'), limit: 0));

        $this->assertEqualsCanonicalizing([(string) $idle->id(), (string) $neverStarted->id()], $this->ids($stale));
    }

    public function testFiltersTheProposalsOlderThanALimit(): void
    {
        $repository = $this->createSUT();
        $userId = UserMother::adminUser()->id();
        $old = ActionPlanMother::proposed($userId, now: new \DateTimeImmutable('2026-11-11 08:00:00'));
        $recent = ActionPlanMother::proposed($userId, now: new \DateTimeImmutable('2026-11-11 09:55:00'));
        $repository->add($old);
        $repository->add($recent);
        $this->clear();

        $this->assertSame([(string) $old->id()], $this->ids($repository->findByFilter(new ActionPlanFilter(proposedBefore: new \DateTimeImmutable('2026-11-11 09:00:00'), limit: 0))));
    }

    public function testTheCostOfAPlanCountsOnTheDayItWasExecuted(): void
    {
        $repository = $this->createSUT();
        $user = UserMother::adminUser();
        $lastNight = new \DateTimeImmutable('2026-11-10 23:50:00');
        $plan = ActionPlanMother::executing($user->id(), now: $lastNight);
        $plan->completeStep(1, 'Hecho', [], null, null, [], $lastNight);
        $plan->completeStep(2, 'Hecho', [], null, null, [], $lastNight);
        $plan->finish(new AssistantUsage('in-memory', 10, 10, 0, 0, '0.500000', 10), new \DateTimeImmutable('2026-11-11 00:10:00'));
        $yesterday = ActionPlanMother::executing($user->id(), now: $lastNight);
        $yesterday->interrupt('Interrumpido', $lastNight, new AssistantUsage('in-memory', 10, 10, 0, 0, '2.000000', 10));
        $ofAnother = ActionPlanMother::executing(UserMother::superAdminUser()->id(), now: new \DateTimeImmutable('2026-11-11 08:00:00'));
        $ofAnother->interrupt('Interrumpido', new \DateTimeImmutable('2026-11-11 08:00:00'), new AssistantUsage('in-memory', 10, 10, 0, 0, '3.000000', 10));

        foreach ([$plan, $yesterday, $ofAnother] as $item) {
            $repository->add($item);
        }
        $this->clear();

        $this->assertSame('0.500000', $repository->costOfUserSince($user->id(), new \DateTimeImmutable('2026-11-11 00:00:00')));
    }

    public function testGroupsTheUsageOfThePlansByDayUserAndModel(): void
    {
        $repository = $this->createSUT();
        $user = UserMother::adminUser();
        $first = ActionPlanMother::executing($user->id(), now: new \DateTimeImmutable('2026-11-11 08:00:00'));
        $first->finish(new AssistantUsage('in-memory', 100, 20, 0, 0, '0.250000', 10), new \DateTimeImmutable('2026-11-11 08:01:00'));
        $second = ActionPlanMother::proposed($user->id(), now: new \DateTimeImmutable('2026-11-10 08:00:00'));
        $repository->add($first);
        $repository->add($second);
        $this->clear();

        $rows = $repository->usageRowsBetween(new \DateTimeImmutable('2026-11-11 00:00:00'), new \DateTimeImmutable('2026-11-12 00:00:00'));

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]->messages);
        $this->assertSame(100, $rows[0]->inputTokens);
        $this->assertSame('0.250000', $rows[0]->costUsd);
        $this->assertSame('in-memory', $rows[0]->model);
    }

    protected function clear(): void {}

    /**
     * @param list<ActionPlan> $plans
     *
     * @return list<string>
     */
    private function ids(array $plans): array
    {
        return array_map(static fn(ActionPlan $plan): string => (string) $plan->id(), $plans);
    }

    abstract protected function createSUT(): ActionPlanRepository;
}
