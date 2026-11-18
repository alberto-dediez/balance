<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\ExpireStale\ExpireStaleAssistantWorkRequest;
use App\Assistant\Application\Conversation\ExpireStale\ExpireStaleAssistantWorkResponse;
use App\Assistant\Application\Conversation\ExpireStale\ExpireStaleAssistantWorkService;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ExpireStaleAssistantWorkServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testFailsStaleMessagesInterruptsStalePlansAndExpiresOldProposals(): void
    {
        $admin = UserMother::adminUser();
        $longAgo = new \DateTimeImmutable('2026-11-11 08:00:00');
        $now = new \DateTimeImmutable('2026-11-11 10:00:00');

        $staleMessageId = MessageId::generate();
        $stale = ConversationMother::waitingForAnswer($admin->id(), $staleMessageId, $longAgo);
        $freshMessageId = MessageId::generate();
        $fresh = ConversationMother::waitingForAnswer($admin->id(), $freshMessageId, new \DateTimeImmutable('2026-11-11 09:58:00'));

        $executing = ConversationMother::answered($admin->id(), now: $longAgo);
        $executionMessageId = MessageId::generate();
        $plan = ActionPlanMother::executing($admin->id(), $executing->id(), $longAgo);
        $executing->addPendingAssistantMessage($executionMessageId, MessageKind::Execution, $longAgo, $plan->id());

        $proposed = ActionPlanMother::proposed($admin->id(), now: $longAgo);

        foreach ([$stale, $fresh, $executing] as $conversation) {
            $this->conversations->add($conversation);
        }
        $this->plans->add($plan);
        $this->plans->add($proposed);

        $response = (new ExpireStaleAssistantWorkService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->settings, $this->liveUpdates, new NullLogger()))(
            new ExpireStaleAssistantWorkRequest($now),
            UserMother::systemUser(),
        );

        $this->assertSame(MessageStatus::Failed, $stale->messageOf($staleMessageId)?->status());
        $this->assertSame('La respuesta se ha interrumpido. Vuelve a intentarlo.', $stale->messageOf($staleMessageId)->error());
        $this->assertSame(MessageStatus::Pending, $fresh->messageOf($freshMessageId)?->status());
        $this->assertSame(ActionPlanStatus::Failed, $plan->status());
        $this->assertSame(MessageStatus::Failed, $executing->messageOf($executionMessageId)?->status());
        $this->assertSame(ActionPlanStatus::Expired, $proposed->status());
        $this->assertSame(1, $response->interruptedPlans);
        $this->assertSame(2, $response->failedMessages);
        $this->assertSame(1, $response->expiredPlans);
        $this->assertCount(1, $this->liveUpdates->touchedForUsers);
    }

    public function testAnExecutionWithRecentProgressIsNotInterrupted(): void
    {
        $admin = UserMother::adminUser();
        $approvedAt = new \DateTimeImmutable('2026-11-11 09:30:00');
        $conversation = ConversationMother::answered($admin->id(), now: $approvedAt);
        $plan = ActionPlanMother::executing($admin->id(), $conversation->id(), $approvedAt);
        $plan->completeStep(1, 'Tarea registrada', ['taskId' => 'T'], null, null, [], new \DateTimeImmutable('2026-11-11 09:58:00'));
        $messageId = MessageId::generate();
        $conversation->addPendingAssistantMessage($messageId, MessageKind::Execution, $approvedAt, $plan->id());
        $this->conversations->add($conversation);
        $this->plans->add($plan);

        $response = $this->expire(new \DateTimeImmutable('2026-11-11 10:00:00'));

        $this->assertSame(ActionPlanStatus::Executing, $plan->status());
        $this->assertSame(0, $response->interruptedPlans);
    }

    public function testAnApprovedPlanThatNeverStartedIsInterruptedFromItsApproval(): void
    {
        $admin = UserMother::adminUser();
        $plan = ActionPlanMother::approved($admin->id(), now: new \DateTimeImmutable('2026-11-11 09:30:00'));
        $this->plans->add($plan);

        $response = $this->expire(new \DateTimeImmutable('2026-11-11 10:00:00'));

        $this->assertSame(ActionPlanStatus::Failed, $plan->status());
        $this->assertSame('La ejecución se ha interrumpido. Revisa los pasos realizados.', $plan->failureReason());
        $this->assertSame(1, $response->interruptedPlans);
    }

    public function testARecentProposalIsKeptAndNothingIsTouched(): void
    {
        $admin = UserMother::adminUser();
        $proposed = ActionPlanMother::proposed($admin->id(), now: new \DateTimeImmutable('2026-11-11 09:30:00'));
        $this->plans->add($proposed);

        $response = $this->expire(new \DateTimeImmutable('2026-11-11 10:00:00'));

        $this->assertSame(ActionPlanStatus::Proposed, $proposed->status());
        $this->assertSame(0, $response->expiredPlans);
        $this->assertSame([], $this->liveUpdates->touchedForUsers);
    }

    public function testAPlanModifiedConcurrentlyEndsThePassWithoutPropagating(): void
    {
        $admin = UserMother::adminUser();
        $longAgo = new \DateTimeImmutable('2026-11-11 08:00:00');
        $plan = ActionPlanMother::approved($admin->id(), now: $longAgo);
        $staleMessageId = MessageId::generate();
        $stale = ConversationMother::waitingForAnswer($admin->id(), $staleMessageId, $longAgo);
        $this->conversations->add($stale);
        $this->plans = new class ([(string) $plan->id() => $plan]) extends InMemoryActionPlanRepository {
            public function add(ActionPlan $plan): void
            {
                throw new ActionPlanConcurrentlyModifiedException($plan->id());
            }
        };

        $response = $this->expire(new \DateTimeImmutable('2026-11-11 10:00:00'));

        $this->assertSame(0, $response->interruptedPlans);
        $this->assertSame(0, $response->failedMessages);
        $this->assertSame(MessageStatus::Pending, $stale->messageOf($staleMessageId)?->status());
        $this->assertSame([], $this->liveUpdates->touchedForUsers);
    }

    public function testAPlanWhoseStoredStatusIsNoLongerActiveIsSkipped(): void
    {
        $admin = UserMother::adminUser();
        $plan = ActionPlanMother::approved($admin->id(), now: new \DateTimeImmutable('2026-11-11 08:00:00'));
        $this->plans = new class ([(string) $plan->id() => $plan]) extends InMemoryActionPlanRepository {
            public function statusOf(ActionPlanId $id): ActionPlanStatus
            {
                return ActionPlanStatus::Executed;
            }
        };

        $response = $this->expire(new \DateTimeImmutable('2026-11-11 10:00:00'));

        $this->assertSame(ActionPlanStatus::Approved, $plan->status());
        $this->assertSame(0, $response->interruptedPlans);
    }

    private function expire(\DateTimeImmutable $now): ExpireStaleAssistantWorkResponse
    {
        return (new ExpireStaleAssistantWorkService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->settings, $this->liveUpdates, new NullLogger()))(
            new ExpireStaleAssistantWorkRequest($now),
            UserMother::systemUser(),
        );
    }
}
