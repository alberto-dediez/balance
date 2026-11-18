<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\GuideArticle;

use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\InvalidGuideArticleException;
use PHPUnit\Framework\TestCase;

class GuideArticlePathTest extends TestCase
{
    /**
     * @dataProvider validPathProvider
     */
    public function testItAcceptsAValidPath(string $value): void
    {
        $this->assertSame($value, (string) new GuideArticlePath($value));
    }

    /**
     * @return iterable<string, list<string>>
     */
    public static function validPathProvider(): iterable
    {
        yield 'one segment' => ['acceso'];
        yield 'two segments' => ['general/acceso'];
        yield 'three segments' => ['seguridad/organizacion/usuarios'];
        yield 'four segments' => ['a/b/c/d'];
        yield 'with numbers and hyphens' => ['mi-perfil-2'];
    }

    /**
     * @dataProvider invalidPathProvider
     */
    public function testItRejectsAnInvalidPath(string $value): void
    {
        $this->expectException(InvalidGuideArticleException::class);

        new GuideArticlePath($value);
    }

    /**
     * @return iterable<string, list<string>>
     */
    public static function invalidPathProvider(): iterable
    {
        yield 'index' => ['index'];
        yield 'traversal' => ['../owner/x'];
        yield 'absolute' => ['/etc/passwd'];
        yield 'uppercase' => ['Seguridad/x'];
        yield 'extension' => ['x.md'];
        yield 'double slash' => ['a//b'];
        yield 'empty' => [''];
        yield 'trailing newline' => ["acceso\n"];
        yield 'too many segments' => ['a/b/c/d/e'];
        yield 'too long' => [str_repeat('a', 201)];
    }

    public function testEquals(): void
    {
        $this->assertTrue((new GuideArticlePath('a/b'))->equals(new GuideArticlePath('a/b')));
        $this->assertFalse((new GuideArticlePath('a/b'))->equals(new GuideArticlePath('a/c')));
    }

    public function testResolveSibling(): void
    {
        $resolved = (new GuideArticlePath('seguridad/organizacion/usuarios'))->resolve('cuentas.md');

        $this->assertSame('seguridad/organizacion/cuentas', (string) $resolved);
    }

    public function testResolveParentDirectory(): void
    {
        $resolved = (new GuideArticlePath('seguridad/organizacion/usuarios'))->resolve('../../general/mi-perfil.md');

        $this->assertSame('general/mi-perfil', (string) $resolved);
    }

    public function testResolveFromARootArticle(): void
    {
        $resolved = (new GuideArticlePath('acceso'))->resolve('general/mi-perfil.md');

        $this->assertSame('general/mi-perfil', (string) $resolved);
    }

    public function testResolveLeavingTheRootReturnsNull(): void
    {
        $this->assertNull((new GuideArticlePath('general/acceso'))->resolve('../../owner/x.md'));
    }

    public function testResolveWithoutMarkdownExtensionReturnsNull(): void
    {
        $this->assertNull((new GuideArticlePath('general/acceso'))->resolve('cuentas'));
    }

    public function testResolveAnAbsoluteUrlReturnsNull(): void
    {
        $this->assertNull((new GuideArticlePath('general/acceso'))->resolve('https://example.com/x.md'));
        $this->assertNull((new GuideArticlePath('general/acceso'))->resolve('/x.md'));
    }

    public function testResolveIndexReturnsNull(): void
    {
        $this->assertNull((new GuideArticlePath('general/acceso'))->resolve('../index.md'));
    }
}
