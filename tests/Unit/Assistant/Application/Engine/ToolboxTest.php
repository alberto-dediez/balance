<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\Engine\DuplicatedToolNameException;
use App\Assistant\Application\Engine\Toolbox;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ToolboxTest extends TestCase
{
    public function testFiltersByModeAndAvailabilitySortedByName(): void
    {
        $toolbox = new Toolbox([
            FakeAssistantTool::read('view_task'),
            FakeAssistantTool::write('register_task'),
            FakeAssistantTool::read('list_clients'),
            new FakeAssistantTool('list_secrets', ToolMode::Read, available: false),
            new FakeAssistantTool('remove_task', ToolMode::Write, available: false),
        ]);

        $user = UserMother::adminUser();

        $this->assertSame(['list_clients', 'view_task'], array_map(static fn($tool) => $tool->name(), $toolbox->readToolsFor($user)));
        $this->assertSame(['register_task'], array_map(static fn($tool) => $tool->name(), $toolbox->writeToolsFor($user)));
        $this->assertNotNull($toolbox->get('list_secrets'));
        $this->assertNull($toolbox->get('unknown'));
        $this->assertSame('Etiqueta de view_task', $toolbox->labels()['view_task']);
    }

    public function testRejectsDuplicatedNames(): void
    {
        $this->expectException(DuplicatedToolNameException::class);

        new Toolbox([FakeAssistantTool::read('list_clients'), FakeAssistantTool::write('list_clients')]);
    }
}
