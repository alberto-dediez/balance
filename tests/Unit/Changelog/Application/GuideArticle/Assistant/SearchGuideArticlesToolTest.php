<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\GuideArticle\Assistant;

use App\Changelog\Application\GuideArticle\Assistant\SearchGuideArticlesTool;
use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Application\GuideArticle\List\ListGuideArticlesService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Changelog\Application\GuideArticle\GuideArticleRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SearchGuideArticlesToolTest extends TestCase
{
    public function testItIsAReadTool(): void
    {
        $sut = $this->createSUT();

        $this->assertSame('search_guide_articles', $sut->name());
        $this->assertSame(ToolMode::Read, $sut->mode());
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
        $this->assertTrue($sut->isAvailableTo(UserMother::ownerUser()));
    }

    public function testItReturnsTheIndexWithAHrefPerArticle(): void
    {
        $sut = $this->createSUT(GuideArticleMother::admin());

        $result = $sut->execute($this->arguments($sut, []), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame('seguridad/organizacion/usuarios', $result->data['articles'][0]['path']);
        $this->assertSame('route:guide_article?path=seguridad%2Forganizacion%2Fusuarios', $result->data['articles'][0]['href']);
        $this->assertNull($result->data['articles'][0]['excerpt']);
    }

    public function testTheExcerptIsOnlyPresentWhenSearchingText(): void
    {
        $sut = $this->createSUT(GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', body: 'Aquí se registra una cuenta'));

        $result = $sut->execute($this->arguments($sut, ['text' => 'cuenta']), UserMother::adminUser(), $this->context());

        $this->assertStringContainsString('cuenta', (string) $result->data['articles'][0]['excerpt']);
    }

    public function testItOnlySearchesTheProfileOfTheUser(): void
    {
        $sut = $this->createSUT(
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/admin', title: 'De admin'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/owner', title: 'De owner'),
        );

        $result = $sut->execute($this->arguments($sut, []), UserMother::ownerUser(), $this->context());

        $this->assertSame(['a/owner'], array_column($result->data['articles'], 'path'));
    }

    public function testItDoesNotAcceptAProfileParameter(): void
    {
        $names = array_map(static fn($parameter): string => $parameter->name, $this->createSUT()->parameters());

        $this->assertSame(['text', 'limit'], $names);
    }

    private function createSUT(GuideArticle ...$articles): SearchGuideArticlesTool
    {
        $authorizer = new GuideArticleAuthorizer();

        return new SearchGuideArticlesTool(new ListGuideArticlesService(GuideArticleRepositoryMother::withArticles(...$articles), $authorizer), $authorizer);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function arguments(SearchGuideArticlesTool $sut, array $values): ToolArguments
    {
        return ToolArgumentsFactory::create($sut->parameters(), $values, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
