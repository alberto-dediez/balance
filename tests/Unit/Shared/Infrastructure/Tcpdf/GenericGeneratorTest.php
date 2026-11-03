<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Tcpdf;

use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use PHPUnit\Framework\TestCase;

final class GenericGeneratorTest extends TestCase
{
    public function testTheMarkdownEscapesRawHtml(): void
    {
        $html = $this->styledHtml('Texto <img src="http://169.254.169.254/x.png"> y <b>negrita</b>' . "\n\n" . '<img src="/etc/logo.png">');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;img src="http://169.254.169.254/x.png"&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;negrita&lt;/b&gt;', $html);
        $this->assertStringContainsString('&lt;img src="/etc/logo.png"&gt;', $html);
    }

    public function testTheMarkdownDropsUnsafeLinks(): void
    {
        $html = $this->styledHtml('[enlace](javascript:alert(1)) y ![imagen](data:text/html;base64,PHNjcmlwdD4=)');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('data:text/html', $html);
    }

    public function testTheMarkdownKeepsItsFormatting(): void
    {
        $html = $this->styledHtml("## Título\n\nTexto con **negrita** y *cursiva*.\n\n- elemento");

        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringContainsString('<em>cursiva</em>', $html);
        $this->assertStringContainsString('<li>', $html);
    }

    public function testTheMarkdownKeepsLineBreaksInParagraphs(): void
    {
        $html = $this->styledHtml("Primera línea<br>segunda<br/>tercera<br />cuarta\n\n<br>");

        $this->assertStringContainsString('Primera línea<br />segunda<br />tercera<br />cuarta', $html);
        $this->assertStringNotContainsString('&lt;br', $html);
    }

    public function testTheMarkdownKeepsLineBreaksInTableCells(): void
    {
        $html = $this->styledHtml("| Columna |\n| --- |\n| uno<br>dos |");

        $this->assertStringContainsString('<td>uno<br />dos</td>', $html);
        $this->assertStringNotContainsString('&lt;br', $html);
    }

    public function testTheMarkdownEscapesLineBreaksWithAttributes(): void
    {
        $html = $this->styledHtml('Texto <br onload="alert(1)"> y <br class="x"/>');

        $this->assertStringNotContainsString('<br', $html);
        $this->assertStringContainsString('&lt;br onload="alert(1)"&gt;', $html);
        $this->assertStringContainsString('&lt;br class="x"/&gt;', $html);
    }

    private function styledHtml(string $markdown): string
    {
        $generator = new class {
            use GenericGenerator;

            public function styledHtml(string $markdown): string
            {
                return $this->markdownToStyledHtml($markdown);
            }
        };

        return $generator->styledHtml($markdown);
    }
}
