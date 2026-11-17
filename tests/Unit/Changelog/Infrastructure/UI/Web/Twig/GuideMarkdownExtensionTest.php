<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\UI\Web\Twig;

use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Changelog\Infrastructure\UI\Web\Twig\GuideMarkdownExtension;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GuideMarkdownExtensionTest extends TestCase
{
    public function testARelativeLinkToAnArticleOpensInTheDocumentModal(): void
    {
        $html = $this->render('[Cuentas](cuentas.md)');

        $this->assertStringContainsString('href="/es/guide/seguridad/organizacion/cuentas"', $html);
        $this->assertStringContainsString('data-assistant-document="1"', $html);
    }

    public function testALinkToAnotherFolderIsResolved(): void
    {
        $html = $this->render('[Perfil](../../general/mi-perfil.md)');

        $this->assertStringContainsString('href="/es/guide/general/mi-perfil"', $html);
    }

    public function testARelativeLinkLeavingTheProfileBecomesText(): void
    {
        $html = $this->render('[Fuera](../../../owner/x.md)');

        $this->assertStringNotContainsString('<a', $html);
        $this->assertStringContainsString('Fuera', $html);
    }

    public function testARelativeLinkToAnythingElseBecomesText(): void
    {
        $html = $this->render('[Hoja](datos.xlsx)');

        $this->assertStringNotContainsString('<a', $html);
        $this->assertStringContainsString('Hoja', $html);
    }

    public function testAnExternalLinkOpensInANewTab(): void
    {
        $html = $this->render('[Web](https://example.com)');

        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('data-assistant-document', $html);
    }

    public function testEmbeddedHtmlIsEscaped(): void
    {
        $this->assertStringNotContainsString('<script>', $this->render('<script>alert(1)</script>'));
    }

    public function testAnImageIsReplacedByItsAlternativeText(): void
    {
        $html = $this->render('![Captura](https://example.com/x.png)');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('Captura', $html);
    }

    public function testTheLeadingTitleIsRemoved(): void
    {
        $html = $this->render("# Usuarios\n\nTexto");

        $this->assertStringNotContainsString('<h1>', $html);
        $this->assertStringContainsString('<p>Texto</p>', $html);
    }

    public function testOnlyTheLeadingTitleIsRemoved(): void
    {
        $html = $this->render("Texto\n\n# Usuarios\n\n## Alta");

        $this->assertStringContainsString('<h1>Usuarios</h1>', $html);
        $this->assertStringContainsString('<h2>Alta</h2>', $html);
    }

    public function testTablesGetTheTableClasses(): void
    {
        $html = $this->render("| A | B |\n|---|---|\n| 1 | 2 |");

        $this->assertStringContainsString('class="table table-striped"', $html);
    }

    private function render(string $body): string
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn(string $route, array $parameters = []): string => '/es/guide/' . $parameters['path'],
        );

        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'seguridad/organizacion/usuarios', body: $body);

        return (new GuideMarkdownExtension($urlGenerator))->toHtml($article);
    }
}
