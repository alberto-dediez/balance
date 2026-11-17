<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Markdown;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Changelog\Domain\Model\GuideArticle\InvalidGuideArticleException;
use App\Changelog\Infrastructure\Markdown\GuideArticleParser;
use PHPUnit\Framework\TestCase;

class GuideArticleParserTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../fixtures/guide/admin';
    }

    public function testACompleteArticleParsesAllFields(): void
    {
        $article = $this->parse('seguridad/organizacion/usuarios');

        $this->assertSame(GuideProfile::Admin, $article->profile);
        $this->assertSame('seguridad/organizacion/usuarios', (string) $article->path);
        $this->assertSame('Usuarios', $article->title);
        $this->assertSame('Alta y gestión de usuarios', $article->summary);
        $this->assertSame('Seguridad > Organización > Usuarios', $article->menu);
        $this->assertSame('2026-11-01 10:30', $article->updated?->format('Y-m-d H:i'));
        $this->assertStringStartsWith('# Usuarios', $article->body);
    }

    public function testADateWithoutTimeIsAccepted(): void
    {
        $this->assertSame('2026-10-05', $this->parse('general/fecha-corta')->updated?->format('Y-m-d'));
    }

    public function testOptionalFieldsDefaultWhenAbsent(): void
    {
        $article = $this->parse('general/fecha-corta');

        $this->assertSame('', $article->menu);
    }

    public function testATitleIsRequired(): void
    {
        $this->expectException(InvalidGuideArticleException::class);

        $this->parse('general/sin-titulo');
    }

    public function testFrontmatterIsRequired(): void
    {
        $this->expectException(InvalidGuideArticleException::class);

        $this->parse('general/sin-frontmatter');
    }

    public function testAnUnreadableFileIsInvalid(): void
    {
        $this->expectException(InvalidGuideArticleException::class);

        $this->parse('general/no-existe');
    }

    private function parse(string $path): GuideArticle
    {
        return (new GuideArticleParser())->parse(
            sprintf('%s/%s.md', $this->fixturesPath, $path),
            GuideProfile::Admin,
            new GuideArticlePath($path),
        );
    }
}
