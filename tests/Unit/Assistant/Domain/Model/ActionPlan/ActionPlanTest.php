<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanExecuted;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFailed;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanProposed;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\DiscardReason;
use App\Assistant\Domain\Model\ActionPlan\InvalidPlanException;
use App\Assistant\Domain\Model\ActionPlan\PlanNotExecutableException;
use App\Assistant\Domain\Model\ActionPlan\PlanStepStatus;
use App\Assistant\Domain\Model\ActionPlan\ProposedArgument;
use App\Assistant\Domain\Model\ActionPlan\ProposedStep;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ActionPlanTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    private UserId $userId;

    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
        $this->userId = UserId::generate();
        $this->now = new \DateTimeImmutable('2026-11-11 10:00:00');
    }

    public function testProposesThePlanWithItsSteps(): void
    {
        $plan = ActionPlanMother::proposed($this->userId);

        $this->assertSame(ActionPlanStatus::Proposed, $plan->status());
        $this->assertCount(2, $plan->steps());
        $this->assertSame(['projectId' => [ActionPlanMother::PROJECT_ID], 'title' => ['Migrar DNS']], $plan->steps()[0]->arguments());
        $this->assertSame(['text' => ['Bajar TTL']], $plan->steps()[1]->arguments());
        $this->assertSame(['taskId' => 1], $plan->steps()[1]->deferred());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(ActionPlanProposed::class, $event);
        $this->assertSame('register_task', $event->payload()['steps'][0]['tool']);
    }

    /**
     * @dataProvider invalidPlans
     *
     * @param list<ProposedStep> $steps
     */
    public function testRejectsInvalidPlans(string $summary, array $steps): void
    {
        $this->expectException(InvalidPlanException::class);

        (new ActionPlan(ActionPlanId::generate()))->propose(ConversationId::generate(), $this->userId, $summary, 'UTC', $steps, $this->now);
    }

    /** @return iterable<string, array{string, list<ProposedStep>}> */
    public static function invalidPlans(): iterable
    {
        $step = new ProposedStep('register_task', 'Registrar tarea', []);

        yield 'empty summary' => ['  ', [$step]];
        yield 'without steps' => ['Resumen', []];
        yield 'too many steps' => ['Resumen', array_fill(0, 16, $step)];
        yield 'from a later step' => ['Resumen', [new ProposedStep('add_task_action', 'Añadir', [new ProposedArgument('taskId', '', null, 1)])]];
        yield 'from a missing step' => ['Resumen', [$step, new ProposedStep('add_task_action', 'Añadir', [new ProposedArgument('taskId', '', null, 0)])]];
    }

    public function testApprovesOnlyAProposedPlanOfItsOwner(): void
    {
        $plan = ActionPlanMother::proposed($this->userId, now: $this->now);

        try {
            $plan->approve(UserId::generate(), $this->now, 60);
            $this->fail('Another user must not approve the plan');
        } catch (PlanNotExecutableException $exception) {
            $this->assertSame('El plan es de otro usuario', $exception->getMessage());
        }

        $plan->approve($this->userId, $this->now, 60);

        $this->assertSame(ActionPlanStatus::Approved, $plan->status());
        $this->assertTrue($this->userId->equals($plan->approvedBy() ?? UserId::generate()));

        $this->expectExceptionMessage('El plan ya no es el último propuesto');

        $plan->approve($this->userId, $this->now, 60);
    }

    public function testAnExpiredPlanCannotBeApproved(): void
    {
        $plan = ActionPlanMother::proposed($this->userId, now: $this->now);

        $this->expectExceptionMessage('El plan ha caducado');

        $plan->approve($this->userId, $this->now->modify('+61 minutes'), 60);
    }

    public function testStartsOnlyWhenApproved(): void
    {
        $this->expectException(PlanNotExecutableException::class);

        ActionPlanMother::proposed($this->userId)->start($this->now);
    }

    public function testExecutesTheStepsInOrder(): void
    {
        $plan = ActionPlanMother::executing($this->userId);

        try {
            $plan->completeStep(2, 'Acción añadida', [], null, null, [], $this->now);
            $this->fail('The second step must not run first');
        } catch (PlanNotExecutableException) {
        }

        $plan->completeStep(1, 'Tarea registrada', ['taskId' => 'T'], 'Ver tarea', 'admin_task_detail', ['taskId'], $this->now);
        $plan->completeStep(2, 'Acción añadida', ['taskId' => 'T', 'actionId' => 'A'], null, null, [], $this->now);
        $plan->finish(new AssistantUsage('m', 1, 1, 0, 0, '0.01', 1), $this->now);

        $this->assertSame(ActionPlanStatus::Executed, $plan->status());
        $this->assertSame(['T'], $plan->steps()[0]->outputValues());
        $this->assertSame(['taskId' => 'T'], $plan->steps()[0]->linkParameters());
        $this->assertSame('0.010000', $plan->usage()->costUsd());
        $this->assertInstanceOf(ActionPlanExecuted::class, $this->spy->lastEvent());
    }

    public function testAFailedStepSkipsTheRest(): void
    {
        $plan = ActionPlanMother::proposed($this->userId, steps: [
            new ProposedStep('register_task', 'Registrar tarea', []),
            new ProposedStep('add_task_action', 'Añadir acción', []),
            new ProposedStep('add_task_action', 'Añadir acción', []),
        ]);
        $plan->approve($this->userId, $this->now, 60);
        $plan->start($this->now);

        $plan->completeStep(1, 'Hecho', [], null, null, [], $this->now);
        $plan->failStep(2, 'Texto demasiado largo', $this->now);
        $plan->finish(AssistantUsage::none(), $this->now);

        $this->assertSame([PlanStepStatus::Done, PlanStepStatus::Failed, PlanStepStatus::Skipped], array_map(static fn($step) => $step->status(), $plan->steps()));
        $this->assertSame(ActionPlanStatus::Failed, $plan->status());
        $this->assertSame('Texto demasiado largo', $plan->failureReason());
    }

    public function testFinishingWithPendingStepsFailsThePlan(): void
    {
        $plan = ActionPlanMother::executing($this->userId);
        $plan->completeStep(1, 'Hecho', [], null, null, [], $this->now);
        $plan->finish(AssistantUsage::none(), $this->now);

        $this->assertSame(ActionPlanStatus::Failed, $plan->status());
        $this->assertSame('El modelo no completó el plan', $plan->failureReason());
        $this->assertSame(PlanStepStatus::Skipped, $plan->steps()[1]->status());
        $this->assertInstanceOf(ActionPlanFailed::class, $this->spy->lastEvent());
    }

    public function testTheThirdRejectedCallIsTooMany(): void
    {
        $plan = ActionPlanMother::executing($this->userId);
        $plan->rejectCall();
        $plan->rejectCall();

        $this->assertFalse($plan->hasTooManyRejectedCalls());

        $plan->rejectCall();
        $plan->finish(AssistantUsage::none(), $this->now);

        $this->assertTrue($plan->hasTooManyRejectedCalls());
        $this->assertSame('Demasiadas llamadas rechazadas', $plan->failureReason());
    }

    public function testInterruptsAnActivePlan(): void
    {
        $plan = ActionPlanMother::approved($this->userId);
        $plan->interrupt('Interrumpido', $this->now);

        $this->assertSame(ActionPlanStatus::Failed, $plan->status());
        $this->assertSame([PlanStepStatus::Skipped, PlanStepStatus::Skipped], array_map(static fn($step) => $step->status(), $plan->steps()));
    }

    public function testDiscardsAndExpiresOnlyAProposedPlan(): void
    {
        $discarded = ActionPlanMother::proposed($this->userId);
        $discarded->discard(DiscardReason::Superseded, $this->now);

        $this->assertSame(ActionPlanStatus::Discarded, $discarded->status());
        $this->assertSame(DiscardReason::Superseded, $discarded->discardReason());

        $expired = ActionPlanMother::proposed($this->userId);
        $expired->expire($this->now);

        $this->assertSame(ActionPlanStatus::Expired, $expired->status());

        $this->expectException(PlanNotExecutableException::class);

        $discarded->expire($this->now);
    }

    public function testTheFailedEventCarriesTheUsageOfTheExecution(): void
    {
        $plan = ActionPlanMother::executing($this->userId);
        $plan->completeStep(1, 'Hecho', [], null, null, [], $this->now);
        $plan->finish(new AssistantUsage('m', 10, 5, 0, 0, '0.250000', 100), $this->now);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(ActionPlanFailed::class, $event);
        $this->assertSame('0.250000', $event->payload()['costUsd']);
        $this->assertSame(10, $event->payload()['inputTokens']);
        $this->assertSame('0.250000', $plan->usage()->costUsd());

        $rebuilt = ActionPlanFailed::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());
        $this->assertSame('0.250000', $rebuilt->costUsd());
        $this->assertSame(5, $rebuilt->usage()->outputTokens());
    }

    public function testTheExecutedEventCarriesTheUsageOfTheExecution(): void
    {
        $plan = ActionPlanMother::executing($this->userId);
        $plan->completeStep(1, 'Hecho', [], null, null, [], $this->now);
        $plan->completeStep(2, 'Hecho', [], null, null, [], $this->now);
        $plan->finish(new AssistantUsage('m', 10, 5, 0, 0, '0.250000', 100), $this->now);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(ActionPlanExecuted::class, $event);
        $this->assertSame('0.250000', $event->payload()['costUsd']);
        $this->assertSame('m', $event->payload()['model']);
    }

    public function testAnInterruptionKeepsTheUsageGivenInItsEvent(): void
    {
        $plan = ActionPlanMother::executing($this->userId);
        $plan->interrupt('Error inesperado', $this->now, new AssistantUsage('m', 1, 1, 0, 0, '0.100000', 1));

        $this->assertSame('0.100000', $plan->usage()->costUsd());
        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(ActionPlanFailed::class, $event);
        $this->assertSame('0.100000', $event->payload()['costUsd']);
    }

    public function testTheProgressIsTrackedOnStartAndOnEveryStep(): void
    {
        $plan = ActionPlanMother::approved($this->userId);

        $this->assertNull($plan->lastProgressAt());

        $plan->start($this->now);
        $this->assertEquals($this->now, $plan->lastProgressAt());

        $plan->completeStep(1, 'Hecho', [], null, null, [], $this->now->modify('+3 minutes'));
        $this->assertEquals($this->now->modify('+3 minutes'), $plan->lastProgressAt());

        $plan->failStep(2, 'Fallo', $this->now->modify('+5 minutes'));
        $this->assertEquals($this->now->modify('+5 minutes'), $plan->lastProgressAt());
    }

    public function testAPlanCanBeApprovedRightAtTheEndOfItsTtl(): void
    {
        $plan = ActionPlanMother::proposed($this->userId, now: $this->now);

        $plan->approve($this->userId, $this->now->modify('+60 minutes'), 60);

        $this->assertSame(ActionPlanStatus::Approved, $plan->status());
    }

    public function testAPlanCannotHaveMoreThanFifteenSteps(): void
    {
        $this->expectException(InvalidPlanException::class);

        ActionPlanMother::proposed($this->userId, steps: array_fill(0, 16, new ProposedStep('register_task', 'Registrar tarea', [])));
    }

    public function testAStepCannotUseTheResultOfItselfNorOfALaterStep(): void
    {
        foreach ([2, 3] as $fromStep) {
            try {
                ActionPlanMother::proposed($this->userId, steps: [
                    new ProposedStep('register_task', 'Registrar tarea', []),
                    new ProposedStep('add_task_action', 'Añadir acción', [new ProposedArgument('taskId', '', null, $fromStep)]),
                    new ProposedStep('add_task_action', 'Añadir acción', []),
                ]);
                $this->fail('The plan must be invalid');
            } catch (InvalidPlanException $exception) {
                $this->assertSame(['Paso 2: «taskId» solo puede usar el resultado de un paso anterior.'], $exception->errors());
            }
        }
    }
}
