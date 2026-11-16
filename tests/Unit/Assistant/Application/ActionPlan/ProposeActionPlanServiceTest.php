<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\ActionPlan;

use App\Assistant\Application\ActionPlan\Propose\ProposeActionPlanRequest;
use App\Assistant\Application\ActionPlan\Propose\ProposeActionPlanResponse;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\InvalidPlanException;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Application\Engine\FakeAssistantTool;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProposeActionPlanServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    private const string PROJECT_ID = '0190f3a4-1b2c-7d3e-8f40-000000000001';

    protected function setUp(): void
    {
        $this->setUpAssistant();
        $this->tools = [
            new FakeAssistantTool('register_task', ToolMode::Write, [ToolParameter::id('projectId', 'Proyecto', '')->required(), ToolParameter::string('title', 'Título', '')->required()], displays: ['projectId' => 'Web Acme']),
            FakeAssistantTool::write('add_task_action', ToolParameter::id('taskId', 'Tarea', '')->required(), ToolParameter::string('text', 'Texto', '')->required(), ToolParameter::string('labels', 'Etiquetas', '')->multiple()),
            FakeAssistantTool::read('list_tasks'),
        ];
    }

    public function testProposesThePlanWithDisplaysAndChainedArguments(): void
    {
        $response = $this->propose([
            ['tool' => 'register_task', 'arguments' => [['name' => 'projectId', 'value' => self::PROJECT_ID, 'fromStep' => null], ['name' => 'title', 'value' => ' Migrar DNS ', 'fromStep' => null]]],
            ['tool' => 'add_task_action', 'arguments' => [['name' => 'taskId', 'value' => '', 'fromStep' => 1], ['name' => 'text', 'value' => 'Bajar TTL', 'fromStep' => null], ['name' => 'labels', 'value' => 'dns', 'fromStep' => null], ['name' => 'labels', 'value' => 'red', 'fromStep' => null]]],
        ]);

        $plan = $this->plans->find($response->planId);
        $this->assertNotNull($plan);
        $steps = $plan->steps();
        $this->assertSame('Etiqueta de register_task', $steps[0]->label());
        $this->assertSame(['projectId' => [self::PROJECT_ID], 'title' => ['Migrar DNS']], $steps[0]->arguments());
        $this->assertSame('Web Acme', $steps[0]->argumentList()[0]->display());
        $this->assertSame(['text' => ['Bajar TTL'], 'labels' => ['dns', 'red']], $steps[1]->arguments());
        $this->assertSame(['taskId' => 1], $steps[1]->deferred());
        $this->assertSame('Europe/Madrid', $plan->timezone());
        $this->assertSame([], $this->tools[0]->executions);
    }

    public function testCollectsTheErrorsOfEveryStep(): void
    {
        try {
            $this->propose([
                ['tool' => 'register_task', 'arguments' => [['name' => 'projectId', 'value' => 'x', 'fromStep' => null]]],
                ['tool' => 'list_tasks', 'arguments' => []],
                ['tool' => 'add_task_action', 'arguments' => [['name' => 'taskId', 'value' => '', 'fromStep' => 3], ['name' => 'text', 'value' => 'Bajar', 'fromStep' => 1]]],
            ]);
            $this->fail('The plan must be invalid');
        } catch (InvalidPlanException $exception) {
            $errors = $exception->errors();

            $this->assertContains('Paso 1: «projectId»: debe ser un identificador UUID válido.', $errors);
            $this->assertContains('Paso 1: «title»: es obligatorio.', $errors);
            $this->assertContains('Paso 2: la herramienta «list_tasks» no es una herramienta de escritura disponible.', $errors);
            $this->assertContains('Paso 3: «taskId» solo puede usar el resultado de un paso anterior.', $errors);
            $this->assertContains('Paso 3: «text» no es un identificador y no puede venir de otro paso.', $errors);
        }

        $this->assertSame(0, $this->plans->totalResultsByFilter(new ActionPlanFilter(limit: 0)));
    }

    public function testThePreviewErrorsAreReported(): void
    {
        $this->tools = [new FakeAssistantTool('register_task', ToolMode::Write, [ToolParameter::string('title', 'Título', '')], previewError: InvalidToolArgumentsException::because('El proyecto no existe.'))];

        $this->expectExceptionMessage('Paso 1: El proyecto no existe.');

        $this->propose([['tool' => 'register_task', 'arguments' => [['name' => 'title', 'value' => 'X', 'fromStep' => null]]]]);
    }

    public function testRejectsAPlanWithoutSteps(): void
    {
        $this->expectException(InvalidPlanException::class);

        $this->propose([]);
    }

    public function testOwnersCannotProposePlans(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->proposeService())(new ProposeActionPlanRequest(ConversationId::generate(), 'Resumen', []), UserMother::ownerUser());
    }

    public function testTheConversationOfAnotherAdminIsDenied(): void
    {
        $conversation = ConversationMother::waitingForAnswer(UserMother::superAdminUser()->id());
        $this->conversations->add($conversation);

        $this->expectException(UnauthorizedActionException::class);

        ($this->proposeService())(new ProposeActionPlanRequest($conversation->id(), 'Resumen', [self::registerStep()]), UserMother::adminUser());
    }

    public function testAConversationWithALivePlanRejectsANewOne(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::waitingForAnswer($admin->id());
        $this->conversations->add($conversation);
        $this->plans->add(ActionPlanMother::proposed($admin->id(), $conversation->id()));

        $this->expectException(ConversationBusyException::class);

        ($this->proposeService())(new ProposeActionPlanRequest($conversation->id(), 'Resumen', [self::registerStep()]), $admin);
    }

    public function testAnUnknownConversationIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->proposeService())(new ProposeActionPlanRequest(ConversationId::generate(), 'Resumen', [self::registerStep()]), UserMother::adminUser());
    }

    /** @return array{tool: string, arguments: list<array{name: string, value: string, fromStep: ?int}>} */
    private static function registerStep(): array
    {
        return ['tool' => 'register_task', 'arguments' => [['name' => 'projectId', 'value' => self::PROJECT_ID, 'fromStep' => null], ['name' => 'title', 'value' => 'Migrar DNS', 'fromStep' => null]]];
    }

    /** @param list<array{tool: string, arguments: list<array{name: string, value: string, fromStep: ?int}>}> $steps */
    private function propose(array $steps): ProposeActionPlanResponse
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::waitingForAnswer($admin->id());
        $this->conversations->add($conversation);

        return ($this->proposeService())(new ProposeActionPlanRequest($conversation->id(), 'Crear la tarea', $steps, 'Europe/Madrid'), $admin);
    }
}
