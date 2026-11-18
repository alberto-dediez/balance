<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Assistant;

use App\Library\Application\Media\Assistant\ListMediaTool;
use App\Library\Application\Media\Assistant\ReadMediaTool;
use App\Shared\Infrastructure\Assistant\DisabledAssistantToolsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DisabledAssistantToolsPassTest extends TestCase
{
    public function testRemovesTheTagOfTheListedToolsOnly(): void
    {
        $container = $this->container([ListMediaTool::class]);

        (new DisabledAssistantToolsPass())->process($container);

        $this->assertFalse($container->getDefinition(ListMediaTool::class)->hasTag('shared.assistant.tool'));
        $this->assertTrue($container->getDefinition(ReadMediaTool::class)->hasTag('shared.assistant.tool'));
    }

    public function testAnEmptyListChangesNothing(): void
    {
        $container = $this->container([]);

        (new DisabledAssistantToolsPass())->process($container);

        $this->assertTrue($container->getDefinition(ListMediaTool::class)->hasTag('shared.assistant.tool'));
        $this->assertTrue($container->getDefinition(ReadMediaTool::class)->hasTag('shared.assistant.tool'));
    }

    public function testFailsWhenAListedToolHasNoDefinition(): void
    {
        $container = $this->container(['App\Unknown\Assistant\MissingTool']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('App\Unknown\Assistant\MissingTool');

        (new DisabledAssistantToolsPass())->process($container);
    }

    /** @param list<string> $disabledTools */
    private function container(array $disabledTools): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('shared.assistant.disabled_tools', $disabledTools);
        $container->register(ListMediaTool::class)->addTag('shared.assistant.tool');
        $container->register(ReadMediaTool::class)->addTag('shared.assistant.tool');

        return $container;
    }
}
