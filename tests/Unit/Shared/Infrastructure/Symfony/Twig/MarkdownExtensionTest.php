<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Twig;

use App\Shared\Infrastructure\Symfony\Twig\MarkdownExtension;
use PHPUnit\Framework\TestCase;

class MarkdownExtensionTest extends TestCase
{
    private MarkdownExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new MarkdownExtension();
    }

    public function testMarkdownIsRenderedAsHtml(): void
    {
        $html = $this->extension->mdToHtml("**Bold** text\n\n- First\n- Second\n\n[Link](https://example.com)");

        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
        $this->assertStringContainsString('<a href="https://example.com">Link</a>', $html);
    }

    public function testRawHtmlIsEscaped(): void
    {
        $html = $this->extension->mdToHtml("<script>alert(1)</script>\n\nText <img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testUnsafeLinksAreRemoved(): void
    {
        $html = $this->extension->mdToHtml('[Click](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $html);
    }
}
