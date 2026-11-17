<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\GuideArticle\View;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Application\GuideArticle\View\ViewGuideArticleRequest;
use App\Changelog\Application\GuideArticle\View\ViewGuideArticleService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleNotFoundException;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Tests\Unit\Changelog\Application\GuideArticle\GuideArticleRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewGuideArticleServiceTest extends TestCase
{
    public function testItReturnsTheArticleOfTheUserProfile(): void
    {
        $article = GuideArticleMother::admin();
        $sut = $this->createSUT($article);

        $response = $sut(new ViewGuideArticleRequest(new GuideArticlePath('seguridad/organizacion/usuarios')), UserMother::adminUser());

        $this->assertSame($article, $response->article);
    }

    public function testEachUserReceivesTheArticleOfTheirOwnProfile(): void
    {
        $admin = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', title: 'De admin');
        $owner = GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/x', title: 'De owner');
        $sut = $this->createSUT($admin, $owner);
        $request = new ViewGuideArticleRequest(new GuideArticlePath('a/x'));

        $this->assertSame('De admin', $sut($request, UserMother::adminUser())->article->title);
        $this->assertSame('De owner', $sut($request, UserMother::ownerUser())->article->title);
    }

    public function testAnArticleOfAnotherProfileIsNotFound(): void
    {
        $sut = $this->createSUT(GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/x'));

        $this->expectException(GuideArticleNotFoundException::class);

        $sut(new ViewGuideArticleRequest(new GuideArticlePath('a/x')), UserMother::adminUser());
    }

    private function createSUT(GuideArticle ...$articles): ViewGuideArticleService
    {
        return new ViewGuideArticleService(GuideArticleRepositoryMother::withArticles(...$articles), new GuideArticleAuthorizer());
    }
}
