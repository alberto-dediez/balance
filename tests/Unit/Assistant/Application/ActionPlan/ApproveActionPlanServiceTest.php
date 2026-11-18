<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\ActionPlan;

use App\Assistant\Application\ActionPlan\Approve\ApproveActionPlanRequest;
use App\Assistant\Application\ActionPlan\Execute\ExecuteActionPlanRequest;
use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Engine\DailyLimitReachedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\DiscardReason;
use App\Assistant\Domain\Model\ActionPlan\PlanNotExecutableException;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Priority;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ApproveActionPlanServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    private User $admin;

    private Conversation $conversation;

    private ActionPlan $plan;

    protected function setUp(): void
    {
        $this->setUpAssistant(now: new \DateTimeImmutable('2026-11-11 10:30:00'));
        $this->admin = UserMother::adminUser();
        $planId = ActionPlanId::generate();
        $this->conversation = ConversationMother::withPlan($this->admin->id(), $planId);
        $this->conversations->add($this->conversation);
        $this->plan = ActionPlanMother::proposed($this->admin->id(), $this->conversation->id(), id: $planId);
        $this->plans->add($this->plan);
    }

    public function testApprovesAndQueuesTheExecution(): void
    {
        $response = ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);

        $this->assertSame(ActionPlanStatus::Approved, $this->plan->status());
        $this->assertTrue($this->conversation->isBusy());
        $message = $this->conversation->messageOf($response->messageId);
        $this->assertSame(MessageKind::Execution, $message?->kind());
        $this->assertTrue($this->plan->id()->equals($message->planId() ?? ActionPlanId::generate()));

        $commands = $this->deferred->commands();
        $this->assertCount(1, $commands);
        $this->assertSame(Priority::HIGH, $commands[0]->priority());
        $this->assertInstanceOf(ExecuteActionPlanRequest::class, $commands[0]->command());
    }

    public function testANotProposedPlanCannotBeApproved(): void
    {
        $this->plan->discard(DiscardReason::User, new \DateTimeImmutable());

        $this->expectExceptionMessage('El plan ya no es el último propuesto');

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);
    }

    public function testAnExpiredPlanCannotBeApproved(): void
    {
        $this->setUpAssistant(now: new \DateTimeImmutable('2026-11-11 11:30:00'));
        $this->conversations->add($this->conversation);
        $this->plans->add($this->plan);

        $this->expectException(PlanNotExecutableException::class);
        $this->expectExceptionMessage('El plan ha caducado');

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);
    }

    public function testThePlanOfAnotherAdminIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), UserMother::superAdminUser());
    }

    public function testTheDailyLimitBlocksTheExecution(): void
    {
        $this->conversations->add(ConversationMother::answered($this->admin->id(), cost: '6.000000', now: new \DateTimeImmutable('2026-11-11 09:00:00')));

        $this->expectException(DailyLimitReachedException::class);

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);
    }

    public function testAConcurrentApprovalFailsAsNotProposed(): void
    {
        $this->plans = new class ([(string) $this->plan->id() => $this->plan]) extends InMemoryActionPlanRepository {
            public function add(ActionPlan $plan): void
            {
                throw new ActionPlanConcurrentlyModifiedException($plan->id());
            }
        };

        $this->expectException(PlanNotExecutableException::class);
        $this->expectExceptionMessage('El plan ya no es el último propuesto');

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);
    }

    public function testABusyConversationCannotApproveThePlan(): void
    {
        $this->conversation->addUserMessage(MessageId::generate(), 'Otra pregunta', '/', 'Inicio', 'Europe/Madrid', new \DateTimeImmutable('2026-11-11 10:20:00'));
        $this->conversation->addPendingAssistantMessage(MessageId::generate(), MessageKind::Text, new \DateTimeImmutable('2026-11-11 10:20:00'));

        $this->expectException(ConversationBusyException::class);

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);
    }

    public function testTheDisabledAssistantCannotApproveThePlan(): void
    {
        $this->setUpAssistant(new AssistantSettings(enabled: false), new \DateTimeImmutable('2026-11-11 10:30:00'));
        $this->conversations->add($this->conversation);
        $this->plans->add($this->plan);

        $this->expectException(UnauthorizedActionException::class);

        ($this->approveService())(new ApproveActionPlanRequest($this->plan->id()), $this->admin);
    }
}
