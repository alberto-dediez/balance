<?php

namespace App\Unit\Shared\Infrastructure\UI\Web\Component\Sidebar;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Shared\Infrastructure\UI\Web\Component\Sidebar\Left;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class LeftTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testComponentMount(): void
    {
        $component = $this->mountTwigComponent(
            name: 'Sidebar:Left',
            data: [],
        );

        $this->assertInstanceOf(Left::class, $component);
    }

    public function testComponentRenders(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'Sidebar:Left',
            data: [],
        );

        $this->assertStringContainsString('sidebarmenu', (string) $rendered);
    }
}