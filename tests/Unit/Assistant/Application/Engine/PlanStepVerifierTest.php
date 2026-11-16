<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\Engine\PlanStepVerifier;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ProposedArgument;
use App\Assistant\Domain\Model\ActionPlan\ProposedStep;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolParameter;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use PHPUnit\Framework\TestCase;

class PlanStepVerifierTest extends TestCase
{
    private const string TASK_ID = '0190f3a4-1b2c-7d3e-8f40-00000000000a';
    private const string OTHER_TASK_ID = '0190f3a4-1b2c-7d3e-8f40-00000000000b';

    private ActionPlan $plan;

    protected function setUp(): void
    {
        $this->plan = ActionPlanMother::executing(UserId::generate());
    }

    public function testTheFirstStepMatchesWithTheSameLiterals(): void
    {
        $this->assertNull($this->verify(1, 'register_task', ['projectId' => ActionPlanMother::PROJECT_ID, 'title' => 'Migrar DNS']));
    }

    public function testTheChainedStepMatchesWithAResultOfThePreviousStep(): void
    {
        $this->completeFirstStep();

        $this->assertNull($this->verify(2, 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
    }

    public function testRejectsAValueThatIsNotAResultOfThePreviousStep(): void
    {
        $this->completeFirstStep();

        $this->assertStringContainsString('resultado del paso 1', (string) $this->verify(2, 'add_task_action', ['taskId' => '0190f3a4-1b2c-7d3e-8f40-0000000000ff', 'text' => 'Bajar TTL']));
    }

    public function testRejectsADifferentLiteral(): void
    {
        $this->completeFirstStep();

        $this->assertStringContainsString('«text»', (string) $this->verify(2, 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar el TTL a 300']));
    }

    public function testRejectsAnotherTool(): void
    {
        $this->completeFirstStep();

        $this->assertStringContainsString('no corresponde al paso 2', (string) $this->verify(2, 'change_task_status', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
    }

    public function testRejectsAnExtraArgument(): void
    {
        $this->assertNotNull($this->verify(1, 'register_task', ['projectId' => ActionPlanMother::PROJECT_ID, 'title' => 'Migrar DNS', 'description' => 'Extra']));
    }

    public function testRejectsAMissingArgument(): void
    {
        $this->assertNotNull($this->verify(1, 'register_task', ['projectId' => ActionPlanMother::PROJECT_ID]));
    }

    public function testADeferredValueMustComeFromTheReferencedStepAndNotFromAnother(): void
    {
        $this->plan = ActionPlanMother::proposed(UserId::generate(), steps: [
            new ProposedStep('register_task', 'Registrar tarea', [new ProposedArgument('title', 'Primera')]),
            new ProposedStep('register_task', 'Registrar tarea', [new ProposedArgument('title', 'Segunda')]),
            new ProposedStep('add_task_action', 'Añadir acción', [new ProposedArgument('taskId', '', null, 1), new ProposedArgument('text', 'Bajar TTL')]),
        ]);
        $this->plan->approve($this->plan->userId() ?? UserId::generate(), new \DateTimeImmutable('2026-11-11 10:00:00'), 60);
        $this->plan->start(new \DateTimeImmutable());
        $this->plan->completeStep(1, 'Tarea registrada', ['taskId' => self::TASK_ID], null, null, [], new \DateTimeImmutable());
        $this->plan->completeStep(2, 'Tarea registrada', ['taskId' => self::OTHER_TASK_ID], null, null, [], new \DateTimeImmutable());

        $this->assertStringContainsString('resultado del paso 1', (string) $this->verify(3, 'add_task_action', ['taskId' => self::OTHER_TASK_ID, 'text' => 'Bajar TTL']));
        $this->assertNull($this->verify(3, 'add_task_action', ['taskId' => self::TASK_ID, 'text' => 'Bajar TTL']));
    }

    public function testAMultipleArgumentMatchesOnlyWithAllItsValues(): void
    {
        $this->plan = ActionPlanMother::proposed(UserId::generate(), steps: [
            new ProposedStep('register_task', 'Registrar tarea', [new ProposedArgument('title', 'Migrar DNS'), new ProposedArgument('labels', 'dns'), new ProposedArgument('labels', 'red')]),
        ]);
        $this->plan->approve($this->plan->userId() ?? UserId::generate(), new \DateTimeImmutable('2026-11-11 10:00:00'), 60);
        $this->plan->start(new \DateTimeImmutable());

        $this->assertNull($this->verify(1, 'register_task', ['title' => 'Migrar DNS', 'labels' => ['dns', 'red']]));
        $this->assertStringContainsString('«labels»', (string) $this->verify(1, 'register_task', ['title' => 'Migrar DNS', 'labels' => ['dns']]));
        $this->assertStringContainsString('«labels»', (string) $this->verify(1, 'register_task', ['title' => 'Migrar DNS', 'labels' => ['dns', 'red', 'web']]));
    }

    private function completeFirstStep(): void
    {
        $this->plan->completeStep(1, 'Tarea registrada', ['taskId' => self::TASK_ID], null, null, [], new \DateTimeImmutable());
    }

    /** @param array<string, mixed> $raw */
    private function verify(int $position, string $tool, array $raw): ?string
    {
        $step = $this->plan->stepAt($position);
        $this->assertNotNull($step);

        return (new PlanStepVerifier())->verify($step, $this->plan, $tool, $this->arguments($raw));
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create(
            [
                ToolParameter::id('projectId', 'Proyecto', ''),
                ToolParameter::id('taskId', 'Tarea', ''),
                ToolParameter::string('title', 'Título', ''),
                ToolParameter::string('text', 'Texto', ''),
                ToolParameter::text('description', 'Descripción', ''),
                ToolParameter::string('labels', 'Etiquetas', '')->multiple(),
            ],
            $raw,
            new ToolContext(new \DateTimeImmutable(), new \DateTimeZone('Europe/Madrid')),
        );
    }
}
