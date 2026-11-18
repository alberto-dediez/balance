<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\GuideArticle;

use App\Changelog\Domain\Model\GuideArticle\GuideArticleFilter;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use PHPUnit\Framework\TestCase;

class GuideArticleTest extends TestCase
{
    public function testMatchesOnlyTheSameProfile(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/b');

        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Owner)));
        $this->assertFalse($article->matches(new GuideArticleFilter(GuideProfile::Admin)));
    }

    public function testMatchesAWordInTheBody(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', body: 'Aquí se registra una cuenta nueva');

        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'cuenta')));
        $this->assertFalse($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'factura')));
    }

    public function testMatchesIgnoringCaseAndAccents(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', body: 'Organización');

        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'ORGANIZACION')));
        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'organización')));
    }

    public function testAllWordsMustAppear(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', body: 'registrar usuarios');

        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'registrar usuarios')));
        $this->assertFalse($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'registrar facturas')));
    }

    public function testMatchesTitleAndMenu(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', title: 'Roles', menu: 'Seguridad > Permisos');

        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'roles')));
        $this->assertTrue($article->matches(new GuideArticleFilter(GuideProfile::Admin, 'permisos')));
    }

    public function testExcerptSurroundsTheFirstWordFound(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', body: 'Primero se abre la pantalla y después se registra la cuenta.');

        $excerpt = $article->excerpt('REGISTRA', 10);

        $this->assertNotNull($excerpt);
        $this->assertStringContainsString('registra', $excerpt);
        $this->assertLessThan(mb_strlen('Primero se abre la pantalla y después se registra la cuenta.'), mb_strlen($excerpt));
    }

    public function testExcerptIgnoresVariationSelectors(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', body: '⚠️ Atención: primero se abre la pantalla y después se registra la cuenta.');

        $this->assertSame('después se registra la cuenta.', $article->excerpt('registra', 12));
    }

    public function testExcerptKeepsTheOriginalAccents(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', body: 'Abre la pestaña Configuración y pulsa Guardar.');

        $this->assertSame('pestaña Configuración y pulsa', $article->excerpt('CONFIGURACION', 8));
    }

    public function testExcerptIsNullWhenNoWordIsInTheBody(): void
    {
        $article = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b', title: 'Facturas', body: 'Cuerpo');

        $this->assertNull($article->excerpt('facturas'));
    }
}
