<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\GuideArticle\List;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Application\GuideArticle\List\ListGuideArticlesRequest;
use App\Changelog\Application\GuideArticle\List\ListGuideArticlesService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Tests\Unit\Changelog\Application\GuideArticle\GuideArticleRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListGuideArticlesServiceTest extends TestCase
{
    public function testOnlyArticlesOfTheUserProfileAreReturned(): void
    {
        $sut = $this->createSUT(
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/admin', body: 'registrar'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/owner', body: 'registrar'),
        );

        $response = $sut(new ListGuideArticlesRequest('registrar'), UserMother::adminUser());

        $this->assertCount(1, $response->matches);
        $this->assertSame('a/admin', (string) $response->matches[0]->article->path);
        $this->assertSame(1, $response->total);
    }

    public function testWithoutTextItReturnsTheIndexOrderedByMenu(): void
    {
        $sut = $this->createSUT(
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'b/x', menu: 'B'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', menu: 'A'),
        );

        $response = $sut(new ListGuideArticlesRequest(null), UserMother::adminUser());

        $this->assertSame('a/x', (string) $response->matches[0]->article->path);
        $this->assertSame('b/x', (string) $response->matches[1]->article->path);
        $this->assertNull($response->matches[0]->excerpt);
    }

    public function testABlankTextIsTreatedAsNoText(): void
    {
        $sut = $this->createSUT(GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x'));

        $response = $sut(new ListGuideArticlesRequest('   '), UserMother::adminUser());

        $this->assertCount(1, $response->matches);
        $this->assertNull($response->matches[0]->excerpt);
    }

    public function testTheExcerptIsOnlyBuiltWithText(): void
    {
        $sut = $this->createSUT(GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', body: 'Se registra una cuenta'));

        $response = $sut(new ListGuideArticlesRequest('cuenta'), UserMother::adminUser());

        $this->assertNotNull($response->matches[0]->excerpt);
    }

    public function testTheLimitIsBounded(): void
    {
        $articles = [];

        for ($i = 0; $i < 60; ++$i) {
            $articles[] = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x' . $i);
        }

        $sut = $this->createSUT(...$articles);

        $this->assertCount(50, $sut(new ListGuideArticlesRequest(null, 500), UserMother::adminUser())->matches);
        $this->assertCount(1, $sut(new ListGuideArticlesRequest(null, 0), UserMother::adminUser())->matches);
        $this->assertSame(60, $sut(new ListGuideArticlesRequest(null, 5), UserMother::adminUser())->total);
    }

    private function createSUT(GuideArticle ...$articles): ListGuideArticlesService
    {
        return new ListGuideArticlesService(GuideArticleRepositoryMother::withArticles(...$articles), new GuideArticleAuthorizer());
    }
}
