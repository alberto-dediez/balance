<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Application\Engine\ToolNotAllowedException;
use App\Assistant\Application\Engine\ToolRunner;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ToolRunnerTest extends TestCase
{
    public function testRunsTheToolWithTheNormalizedArguments(): void
    {
        $tool = FakeAssistantTool::read('list_clients', ToolParameter::string('name', 'Nombre', ''))->willReturn(new ToolResult('1 cliente', ['total' => 1]));

        $outcome = $this->runner($tool)->run('list_clients', ['name' => ' Acme '], ToolMode::Read, UserMother::adminUser(), $this->context());

        $this->assertTrue($outcome->isSuccessful());
        $this->assertSame(['name' => ['Acme']], $outcome->argumentValues());
        $this->assertSame('1 cliente', $outcome->result?->summary);
    }

    public function testRejectsAWriteToolInReadMode(): void
    {
        $tool = FakeAssistantTool::write('register_task');

        try {
            $this->runner($tool)->run('register_task', [], ToolMode::Read, UserMother::adminUser(), $this->context());
            $this->fail('A write tool must not run in read mode');
        } catch (ToolNotAllowedException) {
            $this->assertSame([], $tool->executions);
        }
    }

    public function testRejectsUnknownAndUnavailableTools(): void
    {
        $runner = $this->runner(new FakeAssistantTool('list_secrets', available: false));

        foreach (['unknown', 'list_secrets'] as $name) {
            try {
                $runner->run($name, [], ToolMode::Read, UserMother::adminUser(), $this->context());
                $this->fail('The tool must not run');
            } catch (ToolNotAllowedException) {
            }
        }

        $this->addToAssertionCount(1);
    }

    public function testInvalidArgumentsAreAFailure(): void
    {
        $tool = FakeAssistantTool::read('view_task', ToolParameter::id('taskId', 'Tarea', '')->required());

        $outcome = $this->runner($tool)->run('view_task', [], ToolMode::Read, UserMother::adminUser(), $this->context());

        $this->assertFalse($outcome->isSuccessful());
        $this->assertSame('«taskId»: es obligatorio.', $outcome->error);
        $this->assertSame([], $tool->executions);
    }

    /** @dataProvider exceptions */
    public function testConvertsTheExceptionsIntoFailures(\Throwable $exception, string $expected): void
    {
        $tool = FakeAssistantTool::read('view_task')->willReturn($exception);

        $outcome = $this->runner($tool)->run('view_task', [], ToolMode::Read, UserMother::adminUser(), $this->context());

        $this->assertFalse($outcome->isSuccessful());
        $this->assertStringContainsString($expected, (string) $outcome->error);
    }

    /** @return iterable<string, array{\Throwable, string}> */
    public static function exceptions(): iterable
    {
        yield 'invalid arguments' => [new InvalidToolArgumentsException(['Uno.', 'Dos.']), 'Uno. Dos.'];
        yield 'unauthorized' => [new UnauthorizedActionException(new Account(id: AccountId::generate()), Action::Edit), 'No tienes permiso'];
        yield 'invalid argument' => [new \InvalidArgumentException('Task not found'), 'Task not found'];
        yield 'domain' => [new \DomainException('Ya está cerrada'), 'Ya está cerrada'];
        yield 'unexpected' => [new \RuntimeException('SQLSTATE secreto'), 'Error inesperado'];
    }

    private function runner(FakeAssistantTool ...$tools): ToolRunner
    {
        return new ToolRunner(new Toolbox($tools), new NullLogger());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('UTC'));
    }
}
