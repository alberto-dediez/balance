<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Twig;

use App\Shared\Infrastructure\Symfony\Twig\MarkdownExtension;
use PHPUnit\Framework\TestCase;

class MarkdownExtensionTest extends TestCase
{
    public function testItConvertsMarkdownToHtml(): void
    {
        $sut = new MarkdownExtension();

        $this->assertStringContainsString('<strong>importante</strong>', $sut->mdToHtml('**importante**'));
    }

    public function testItEscapesEmbeddedHtml(): void
    {
        $sut = new MarkdownExtension();

        $this->assertStringNotContainsString('<script>', $sut->mdToHtml('<script>alert(1)</script>'));
    }

    public function testItDoesNotProduceAnExecutableJavascriptLink(): void
    {
        $sut = new MarkdownExtension();

        $this->assertStringNotContainsString('href="javascript:', $sut->mdToHtml('[click](javascript:alert(1))'));
    }

    public function testItOpensLinksInANewTab(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml('[Guía](https://example.com/guia)');

        $this->assertStringContainsString('href="https://example.com/guia"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('noopener', $html);
        $this->assertStringContainsString('noreferrer', $html);
    }

    public function testItOpensAutolinkedUrlsInANewTab(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml('Consulta https://example.com');

        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function testItKeepsRelativeLinksInTheSameTab(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml('[Inicio](/es/)');

        $this->assertStringContainsString('href="/es/"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }
}
