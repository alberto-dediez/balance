<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\Engine\AssistantPrompts;
use PHPUnit\Framework\TestCase;

class AssistantPromptsTest extends TestCase
{
    public function testTheQuerySystemAsksToLinkWithTheHrefOfTheResults(): void
    {
        $system = (new AssistantPrompts())->querySystem([]);

        $this->assertStringContainsString('Los resultados traen campos «href» (empiezan por «route:»)', $system);
        $this->assertStringContainsString('[nombre](href)', $system);
    }

    public function testTheQuerySystemAsksToFindScreensWithListScreens(): void
    {
        $system = (new AssistantPrompts())->querySystem([]);

        $this->assertStringContainsString('búscala con list_screens y enlázala', $system);
    }
}
