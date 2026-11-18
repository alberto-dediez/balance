<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleFilter;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use PHPUnit\Framework\TestCase;

abstract class GuideArticleRepositoryTest extends TestCase
{
    /**
     * @param list<GuideArticle> $articles
     */
    abstract protected function givenArticles(array $articles): GuideArticleRepository;

    public function testArticlesAreOrderedByMenuAndThenPath(): void
    {
        $repository = $this->givenArticles([
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'b/x', menu: 'B'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/z', menu: 'A'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/y', menu: 'A'),
        ]);

        $result = $repository->findByFilter(new GuideArticleFilter(GuideProfile::Admin));

        $this->assertSame(['a/y', 'a/z', 'b/x'], array_map(static fn(GuideArticle $article): string => (string) $article->path, $result));
    }

    public function testOnlyTheArticlesOfTheProfileAreReturned(): void
    {
        $repository = $this->givenArticles([
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/y'),
        ]);

        $filter = new GuideArticleFilter(GuideProfile::Owner);

        $this->assertCount(1, $repository->findByFilter($filter));
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
    }

    public function testFilterByText(): void
    {
        $repository = $this->givenArticles([
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x', title: 'Altas', body: 'Se registra un usuario'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/y', title: 'Facturas', body: 'Otra cosa'),
        ]);

        $result = $repository->findByFilter(new GuideArticleFilter(GuideProfile::Admin, 'registra'));

        $this->assertCount(1, $result);
        $this->assertSame('a/x', (string) $result[0]->path);
    }

    public function testPaginationAndTotal(): void
    {
        $repository = $this->givenArticles([
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/one'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/two'),
            GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/three'),
        ]);

        $filter = new GuideArticleFilter(GuideProfile::Admin);
        $filter->limit = 2;

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));

        $filter->page = 1;

        $this->assertCount(1, $repository->findByFilter($filter));
    }

    public function testFindExisting(): void
    {
        $repository = $this->givenArticles([GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x')]);

        $found = $repository->find(GuideProfile::Admin, new GuideArticlePath('a/x'));

        $this->assertNotNull($found);
        $this->assertSame('a/x', (string) $found->path);
    }

    public function testFindFromAnotherProfileReturnsNull(): void
    {
        $repository = $this->givenArticles([GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/x')]);

        $this->assertNull($repository->find(GuideProfile::Owner, new GuideArticlePath('a/x')));
    }

    public function testFindMissingReturnsNull(): void
    {
        $repository = $this->givenArticles([]);

        $this->assertNull($repository->find(GuideProfile::Admin, new GuideArticlePath('a/x')));
    }
}
