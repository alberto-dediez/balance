<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\ActionPlan;

use App\Assistant\Application\ActionPlan\Execute\ExecuteActionPlanRequest;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\PlanStepStatus;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Assistant\CurrentAssistantExecution;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Application\Engine\FakeAssistantTool;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExecuteActionPlanServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    private const string TASK_ID = '0190f3a4-1b2c-7d3e-8f40-00000000000a';

    private User $admin;

    private Conversation $conversation;

    private ActionPlan $plan;

    private MessageId $messageId;

    private FakeAssistantTool $registerTask;

    private FakeAssistantTool $addAction;

    /** @var \ArrayObject<int, ?string> */
    private \ArrayObject $executionIds;

    protected function setUp(): void
    {
        $this->setUpAssistant();
        $this->executionIds = new \ArrayObject();

        $this->registerTask = new class ('register_task', ToolMode::Write, [ToolParameter::id('projectId', 'Proyecto', '')->required(), ToolParameter::string('title', 'Título', '')->required()], execution: $this->execution, executionIds: $this->executionIds) extends FakeAssistantTool {
            /**
             * @param list<ToolParameter>        $parameters
             * @param \ArrayObject<int, ?string> $executionIds
             */
            public function __construct(string $name, ToolMode $mode, array $parameters, private readonly CurrentAssistantExecution $execution, private readonly \ArrayObject $executionIds)
            {
                parent::__construct($name, $mode, $parameters);
            }

            public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
            {
                $this->executionIds[] = $this->execution->executionId();

                return parent::execute($arguments, $user, $context);
            }
        };
        $this->registerTask->willReturn(new ToolResult('Tarea registrada', ['taskId' => self::TASK_ID], ['taskId' => self::TASK_ID], new ToolLink('Ver tarea', 'admin_task_detail', ['taskId'])));
        $this->addAction = FakeAssistantTool::write('add_task_action', ToolParameter::id('taskId', 'Tarea', '')->required(), ToolParameter::string('text', 'Texto', '')->required())
            ->willReturn(new ToolResult('Acción añadida', [], ['taskId' => self::TASK_ID, 'actionId' => 'A']));
        $this->tools = [$this->registerTask, $this->addAction];

        $this->admin = UserMother::adminUser();
        $planId = ActionPlanId::generate();
        $this->conversation = ConversationMother::withPlan($this->admin->id(), $planId);
        $this->plan = ActionPlanMother::proposed($this->admin->id(), $this->conversation->id(), id: $planId);
        $this->plan->approve($this->admin->id(), new \DateTimeImmutable('2026-11-11 10:00:00'), 60);
        $this->messageId = MessageId::generate();
        $this->conversation->addPendingAssistantMessage($this->messageId, MessageKind::Execution, new \DateTimeImmutable(), $planId);
        $this->conversations->add($this->conversation);
        $this->plans->add($this->plan);
    }

    public function testExecutesTheWholePlanChainingTheResults(): void
    {
        $this->client->enqueueToolCalls($this->registerCall());
        $this->client->enqueueToolCalls(new AiToolCall('c2', 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        $this->client->enqueueText('He creado la tarea con su acción.');

        $this->execute();

        $this->assertSame(ActionPlanStatus::Executed, $this->plan->status());
        $this->assertSame([PlanStepStatus::Done, PlanStepStatus::Done], $this->statuses());
        $this->assertSame('admin_task_detail', $this->plan->steps()[0]->linkRoute());
        $this->assertSame(['taskId' => self::TASK_ID], $this->plan->steps()[0]->linkParameters());
        $this->assertCount(1, $this->addAction->executions);
        $this->assertSame('He creado la tarea con su acción.', $this->conversation->messageOf($this->messageId)?->text());
        $this->assertSame('0.000600', $this->plan->usage()->costUsd());
        $lastRequest = $this->client->lastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertFalse($lastRequest->parallelToolCalls);
        $this->assertSame(['add_task_action', 'register_task'], array_map(static fn($tool) => $tool->name, $lastRequest->tools));
    }

    public function testADifferentLiteralIsRejectedAndNotExecuted(): void
    {
        $this->client->enqueueToolCalls(new AiToolCall('c1', 'register_task', ['projectId' => ActionPlanMother::PROJECT_ID, 'title' => 'Otra cosa']));
        $this->client->enqueueText('No he podido.');

        $this->execute();

        $this->assertSame([], $this->registerTask->executions);
        $this->assertSame(1, $this->plan->rejectedCalls());
        $this->assertSame(ActionPlanStatus::Failed, $this->plan->status());
        $this->assertSame([PlanStepStatus::Skipped, PlanStepStatus::Skipped], $this->statuses());
        $this->assertStringStartsWith('Se han ejecutado 0 de 2 pasos.', (string) $this->conversation->messageOf($this->messageId)?->text());
    }

    public function testTheThirdRejectedCallFailsThePlan(): void
    {
        foreach (range(1, 3) as $index) {
            $this->client->enqueueToolCalls(new AiToolCall('c' . $index, 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        }
        $this->client->enqueueToolCalls($this->registerCall());

        $this->execute();

        $this->assertSame(ActionPlanStatus::Failed, $this->plan->status());
        $this->assertSame('Demasiadas llamadas rechazadas', $this->plan->failureReason());
        $this->assertSame([], $this->registerTask->executions);
        $this->assertCount(3, $this->client->requests());
    }

    public function testAFailingStepStopsTheExecution(): void
    {
        $this->addAction = FakeAssistantTool::write('add_task_action', ToolParameter::id('taskId', 'Tarea', '')->required(), ToolParameter::string('text', 'Texto', '')->required())
            ->willReturn(new \DomainException('La tarea está cerrada'));
        $this->tools = [$this->registerTask, $this->addAction];
        $this->client->enqueueToolCalls($this->registerCall());
        $this->client->enqueueToolCalls(new AiToolCall('c2', 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        $this->client->enqueueText('Debería no llegar aquí.');

        $this->execute();

        $this->assertSame([PlanStepStatus::Done, PlanStepStatus::Failed], $this->statuses());
        $this->assertSame(ActionPlanStatus::Failed, $this->plan->status());
        $this->assertSame('Se han ejecutado 1 de 2 pasos. El paso 2 ha fallado: La tarea está cerrada', $this->conversation->messageOf($this->messageId)?->text());
        $this->assertSame(1, $this->client->pending());
    }

    public function testTheModelEndingEarlyFailsThePlan(): void
    {
        $this->client->enqueueToolCalls($this->registerCall());
        $this->client->enqueueText('Listo.');

        $this->execute();

        $this->assertSame(ActionPlanStatus::Failed, $this->plan->status());
        $this->assertSame([PlanStepStatus::Done, PlanStepStatus::Skipped], $this->statuses());
        $this->assertSame('Se han ejecutado 1 de 2 pasos. El modelo no completó el plan', $this->conversation->messageOf($this->messageId)?->text());
    }

    public function testARedeliveryDoesNothing(): void
    {
        $this->client->enqueueToolCalls($this->registerCall());
        $this->client->enqueueToolCalls(new AiToolCall('c2', 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        $this->client->enqueueText('Hecho.');
        $this->execute();

        $this->execute();

        $this->assertCount(1, $this->registerTask->executions);
        $this->assertCount(3, $this->client->requests());
    }

    public function testTheExecutionIsMarkedWhileRunningAndCleanedAfter(): void
    {
        $this->client->enqueueToolCalls($this->registerCall());

        $this->execute();

        $this->assertSame([(string) $this->plan->id()], $this->executionIds->getArrayCopy());
        $this->assertNull($this->execution->executionId());
    }

    public function testAnExceptionFailsThePlanWithoutPropagatingAndCleansTheExecution(): void
    {
        $this->plans = new class ([(string) $this->plan->id() => $this->plan]) extends InMemoryActionPlanRepository {
            private int $saves = 0;

            public function add(ActionPlan $plan): void
            {
                if (++$this->saves > 1) {
                    throw new \RuntimeException('Database down');
                }

                parent::add($plan);
            }
        };
        $this->client->enqueueToolCalls($this->registerCall());

        $this->execute();

        $this->assertSame(ActionPlanStatus::Failed, $this->plan->status());
        $this->assertSame('Error inesperado', $this->plan->failureReason());
        $this->assertNull($this->execution->executionId());
    }

    public function testAPlanInterruptedBetweenTwoStepsDoesNotRunTheNextOneNorEndsExecuted(): void
    {
        $plans = new class ([(string) $this->plan->id() => $this->plan]) extends InMemoryActionPlanRepository {
            public bool $interrupted = false;

            public function statusOf(ActionPlanId $id): ?ActionPlanStatus
            {
                return $this->interrupted ? ActionPlanStatus::Failed : parent::statusOf($id);
            }
        };
        $this->plans = $plans;
        $this->registerTask->onExecute(static function () use ($plans): void {
            $plans->interrupted = true;
        });
        $this->client->enqueueToolCalls($this->registerCall());
        $this->client->enqueueToolCalls(new AiToolCall('c2', 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        $this->client->enqueueText('Hecho.');

        $this->execute();

        $this->assertSame([], $this->addAction->executions);
        $this->assertNotSame(ActionPlanStatus::Executed, $this->plan->status());
        $this->assertCount(1, $this->client->requests());
        $this->assertTrue($this->conversation->messageOf($this->messageId)?->isPending());
        $this->assertNull($this->execution->executionId());
    }

    public function testAConcurrentStartEndsWithoutRunningAnything(): void
    {
        $this->plans = new class ([(string) $this->plan->id() => $this->plan]) extends InMemoryActionPlanRepository {
            public function add(ActionPlan $plan): void
            {
                throw new ActionPlanConcurrentlyModifiedException($plan->id());
            }
        };
        $this->client->enqueueToolCalls($this->registerCall());

        $this->execute();

        $this->assertSame([], $this->registerTask->executions);
        $this->assertCount(0, $this->client->requests());
        $this->assertNull($this->execution->executionId());
    }

    public function testOnlyTheFirstCallOfATurnIsExecuted(): void
    {
        $this->client->enqueueToolCalls($this->registerCall(), new AiToolCall('c2', 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        $this->client->enqueueToolCalls(new AiToolCall('c3', 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
        $this->client->enqueueText('Hecho.');

        $this->execute();

        $this->assertCount(1, $this->registerTask->executions);
        $this->assertCount(1, $this->addAction->executions);
        $this->assertSame(ActionPlanStatus::Executed, $this->plan->status());
        $this->assertSame(0, $this->plan->rejectedCalls());
    }

    private function registerCall(): AiToolCall
    {
        return new AiToolCall('c1', 'register_task', ['projectId' => ActionPlanMother::PROJECT_ID, 'title' => 'Migrar DNS']);
    }

    /** @return list<PlanStepStatus> */
    private function statuses(): array
    {
        return array_map(static fn($step): PlanStepStatus => $step->status(), $this->plan->steps());
    }

    private function execute(): void
    {
        ($this->executeService())(new ExecuteActionPlanRequest($this->plan->id(), $this->messageId), $this->admin);
    }
}
