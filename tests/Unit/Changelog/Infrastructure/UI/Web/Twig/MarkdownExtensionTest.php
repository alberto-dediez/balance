<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\UI\Web\Twig;

use App\Changelog\Infrastructure\UI\Web\Twig\MarkdownExtension;
use PHPUnit\Framework\TestCase;

class MarkdownExtensionTest extends TestCase
{
    public function testItConvertsHeadingsToHtml(): void
    {
        $sut = new MarkdownExtension();

        $this->assertStringContainsString('<h1>Título</h1>', $sut->toHtml('# Título'));
    }

    public function testItEscapesEmbeddedHtml(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->toHtml('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testItDoesNotProduceAnExecutableJavascriptLink(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->toHtml('[click](javascript:alert(1))');

        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function testItRendersTables(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->toHtml("| A | B |\n| --- | --- |\n| 1 | 2 |");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>A</th>', $html);
        $this->assertStringContainsString('<td>1</td>', $html);
    }
}
