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

    public function testItRendersLineBreaksBetweenLines(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml("*NIVEL 1* Sin evidencias\n*NIVEL 2* Con evidencias");

        $this->assertStringContainsString('<em>NIVEL 1</em> Sin evidencias<br>', $html);
    }

    public function testItRendersTheHtmlTagsWrittenByTheEditor(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml('<strong>EVIDENCIA:</strong>informe<br><em>fechado</em> <b>hoy</b> <br />fin');

        $this->assertSame("<p><strong>EVIDENCIA:</strong>informe<br><em>fechado</em> <b>hoy</b> <br>fin</p>\n", $html);
    }

    public function testItParsesTheMarkdownFollowingAnEmptyLineWrittenByTheEditor(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml("Primero\n\n<br>\n**Segundo**");

        $this->assertStringContainsString('<br>', $html);
        $this->assertStringContainsString('<strong>Segundo</strong>', $html);
        $this->assertStringNotContainsString('&lt;br&gt;', $html);
    }

    public function testItEscapesAllowedTagsCarryingAttributes(): void
    {
        $sut = new MarkdownExtension();

        $html = $sut->mdToHtml('<strong onclick="alert(1)">texto</strong> <img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<strong onclick', $html);
        $this->assertStringNotContainsString('<img', $html);
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
