<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\Stop\StopConversationWorkRequest;
use App\Assistant\Application\Conversation\Stop\StopConversationWorkService;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFailed;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\PlanStepStatus;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryConversationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class StopConversationWorkServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testStopsAPendingAnswer(): void
    {
        $admin = UserMother::adminUser();
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer($admin->id(), $messageId);
        $this->conversations->add($conversation);

        $response = $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);

        $message = $conversation->messageOf($messageId);
        $this->assertTrue($response->stopped);
        $this->assertSame(MessageStatus::Failed, $message?->status());
        $this->assertSame('Respuesta detenida', $message->error());
        $this->assertFalse($conversation->isBusy());
        $this->assertSame([[$admin->id(), 'assistant']], $this->liveUpdates->touchedForUsers);
    }

    public function testInterruptsARunningPlanKeepingTheDoneSteps(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $executionId = MessageId::generate();
        $plan = ActionPlanMother::executing($admin->id(), $conversation->id());
        $conversation->addPendingAssistantMessage($executionId, MessageKind::Execution, new \DateTimeImmutable(), $plan->id());
        $this->conversations->add($conversation);
        $this->plans->add($plan);

        $response = $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);

        $this->assertTrue($response->stopped);
        $this->assertSame(ActionPlanStatus::Failed, $plan->status());
        $this->assertSame('Ejecución detenida. Revisa los pasos realizados.', $plan->failureReason());
        $this->assertSame('Ejecución detenida. Revisa los pasos realizados.', $conversation->messageOf($executionId)?->error());
        $this->assertSame([], array_filter($plan->steps(), static fn($step): bool => PlanStepStatus::Pending === $step->status()));
    }

    public function testAPlanModifiedConcurrentlyLeavesTheMessageUntouched(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $executionId = MessageId::generate();
        $plan = ActionPlanMother::executing($admin->id(), $conversation->id());
        $conversation->addPendingAssistantMessage($executionId, MessageKind::Execution, new \DateTimeImmutable(), $plan->id());
        $this->conversations->add($conversation);
        $this->plans = new class ([(string) $plan->id() => $plan]) extends InMemoryActionPlanRepository {
            public function add(ActionPlan $plan): void
            {
                throw new ActionPlanConcurrentlyModifiedException($plan->id());
            }
        };

        $response = $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);

        $this->assertFalse($response->stopped);
        $this->assertSame(MessageStatus::Pending, $conversation->messageOf($executionId)?->status());
        $this->assertSame([], $this->liveUpdates->touchedForUsers);
    }

    public function testAPlanModifiedConcurrentlyDiscardsItsPendingEvents(): void
    {
        $spySubscriber = new SpyDomainEventSubscriber();
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $plan = ActionPlanMother::executing($admin->id(), $conversation->id());
        $conversation->addPendingAssistantMessage(MessageId::generate(), MessageKind::Execution, new \DateTimeImmutable(), $plan->id());
        $this->conversations->add($conversation);
        $this->plans = new class ([(string) $plan->id() => $plan]) extends InMemoryActionPlanRepository {
            public function add(ActionPlan $plan): void
            {
                throw new ActionPlanConcurrentlyModifiedException($plan->id());
            }
        };

        DomainEventPublisher::instance()->lock();

        try {
            $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);
        } finally {
            DomainEventPublisher::instance()->unlock();
        }

        $this->assertSame([], array_filter($spySubscriber->events(), static fn($event): bool => $event instanceof ActionPlanFailed));
    }

    public function testAMessageCompletedMeanwhileIsNotFailed(): void
    {
        $admin = UserMother::adminUser();
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer($admin->id(), $messageId);
        $this->conversations = new class ([(string) $conversation->id() => $conversation]) extends InMemoryConversationRepository {
            public function messageStatusOf(MessageId $id): MessageStatus
            {
                return MessageStatus::Completed;
            }
        };

        $response = $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);

        $this->assertFalse($response->stopped);
        $this->assertSame(MessageStatus::Pending, $conversation->messageOf($messageId)?->status());
    }

    public function testAnExecutionWhosePlanJustFinishedIsStoppedAsAnExecution(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $executionId = MessageId::generate();
        $plan = ActionPlanMother::executing($admin->id(), $conversation->id());
        $plan->interrupt('Terminado', new \DateTimeImmutable());
        $conversation->addPendingAssistantMessage($executionId, MessageKind::Execution, new \DateTimeImmutable(), $plan->id());
        $this->conversations->add($conversation);
        $this->plans = new class ([(string) $plan->id() => $plan]) extends InMemoryActionPlanRepository {
            public function findByFilter(ActionPlanFilter $filter): array
            {
                return parent::findByFilter(new ActionPlanFilter(conversationId: $filter->conversationId, limit: 0));
            }
        };

        $response = $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);

        $this->assertTrue($response->stopped);
        $this->assertSame(StopConversationWorkService::STOPPED_PLAN, $conversation->messageOf($executionId)?->error());
    }

    public function testNothingPendingHasNoEffect(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);

        $response = $this->service()(new StopConversationWorkRequest($conversation->id()), $admin);

        $this->assertFalse($response->stopped);
        $this->assertSame([], $this->liveUpdates->touchedForUsers);
    }

    public function testAnotherAdminIsDenied(): void
    {
        $conversation = ConversationMother::waitingForAnswer(UserMother::superAdminUser()->id());
        $this->conversations->add($conversation);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new StopConversationWorkRequest($conversation->id()), UserMother::adminUser());
    }

    private function service(): StopConversationWorkService
    {
        return new StopConversationWorkService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->liveUpdates, $this->clock);
    }
}
