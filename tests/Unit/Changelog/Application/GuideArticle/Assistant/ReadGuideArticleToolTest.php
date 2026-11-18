<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\GuideArticle\Assistant;

use App\Changelog\Application\GuideArticle\Assistant\ReadGuideArticleTool;
use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Application\GuideArticle\View\ViewGuideArticleService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Changelog\Application\GuideArticle\GuideArticleRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReadGuideArticleToolTest extends TestCase
{
    public function testItReturnsTheArticle(): void
    {
        $sut = $this->createSUT(GuideArticleMother::admin());

        $result = $sut->execute($this->arguments($sut, 'seguridad/organizacion/usuarios'), UserMother::adminUser(), $this->context());

        $this->assertSame('Usuarios', $result->summary);
        $this->assertSame('seguridad/organizacion/usuarios', $result->data['path']);
        $this->assertSame('Cuerpo de prueba', $result->data['markdown']);
        $this->assertSame('2026-11-01 11:00', substr((string) $result->data['updated'], 0, 16));
        $this->assertFalse($result->data['truncated']);
        $this->assertSame(['path' => 'seguridad/organizacion/usuarios'], $result->references);
        $this->assertSame('guide_article', $result->link?->route);
        $this->assertSame(['path'], $result->link->parameters);
    }

    public function testItIsAReadToolAvailableToEveryProfile(): void
    {
        $sut = $this->createSUT();

        $this->assertSame('read_guide_article', $sut->name());
        $this->assertSame(ToolMode::Read, $sut->mode());
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
        $this->assertTrue($sut->isAvailableTo(UserMother::ownerUser()));
    }

    public function testItRejectsAnInvalidPath(): void
    {
        $sut = $this->createSUT();

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('no es una ruta de artículo válida');

        $sut->execute($this->arguments($sut, '../owner/x'), UserMother::adminUser(), $this->context());
    }

    public function testAMissingArticleIsNotFound(): void
    {
        $sut = $this->createSUT();

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('El artículo no existe');

        $sut->execute($this->arguments($sut, 'a/x'), UserMother::adminUser(), $this->context());
    }

    public function testAnOwnerCannotReadAnAdminArticle(): void
    {
        $sut = $this->createSUT(GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x'));

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('El artículo no existe');

        $sut->execute($this->arguments($sut, 'a/x'), UserMother::ownerUser(), $this->context());
    }

    public function testAnOwnerReadsTheOwnerArticle(): void
    {
        $sut = $this->createSUT(
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', title: 'De admin'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/x', title: 'De owner'),
        );

        $result = $sut->execute($this->arguments($sut, 'a/x'), UserMother::ownerUser(), $this->context());

        $this->assertSame('De owner', $result->data['title']);
    }

    public function testALongBodyIsTruncated(): void
    {
        $sut = $this->createSUT(GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', body: str_repeat('a', 20001)));

        $result = $sut->execute($this->arguments($sut, 'a/x'), UserMother::adminUser(), $this->context());

        $this->assertTrue($result->data['truncated']);
        $this->assertSame(20000, mb_strlen($result->data['markdown']));
    }

    public function testSourceHref(): void
    {
        $sut = $this->createSUT();

        $this->assertSame('route:guide_article?path=a%2Fx', $sut->sourceHref(['path' => ['a/x']]));
        $this->assertNull($sut->sourceHref([]));
    }

    public function testItDoesNotAcceptAProfileParameter(): void
    {
        $names = array_map(static fn($parameter): string => $parameter->name, $this->createSUT()->parameters());

        $this->assertSame(['path'], $names);
    }

    private function createSUT(GuideArticle ...$articles): ReadGuideArticleTool
    {
        $authorizer = new GuideArticleAuthorizer();

        return new ReadGuideArticleTool(new ViewGuideArticleService(GuideArticleRepositoryMother::withArticles(...$articles), $authorizer), $authorizer);
    }

    private function arguments(ReadGuideArticleTool $sut, string $path): ToolArguments
    {
        return ToolArgumentsFactory::create($sut->parameters(), ['path' => $path], $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
