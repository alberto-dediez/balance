<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\InMemory;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleFilter;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;

class InMemoryGuideArticleRepository implements GuideArticleRepository
{
    /** @var list<GuideArticle> */
    private array $articles = [];

    public function add(GuideArticle $article): void
    {
        $this->articles[] = $article;
    }

    public function clear(): void
    {
        $this->articles = [];
    }

    public function find(GuideProfile $profile, GuideArticlePath $path): ?GuideArticle
    {
        foreach ($this->articles as $article) {
            if ($article->profile === $profile && $article->path->equals($path)) {
                return $article;
            }
        }

        return null;
    }

    public function findByFilter(GuideArticleFilter $filter): array
    {
        $articles = $this->matching($filter);

        usort($articles, static fn(GuideArticle $a, GuideArticle $b): int => [$a->menu, (string) $a->path] <=> [$b->menu, (string) $b->path]);

        return array_slice($articles, $filter->page * $filter->limit, $filter->limit);
    }

    public function totalResultsByFilter(GuideArticleFilter $filter): int
    {
        return count($this->matching($filter));
    }

    /**
     * @return list<GuideArticle>
     */
    private function matching(GuideArticleFilter $filter): array
    {
        return array_values(array_filter($this->articles, static fn(GuideArticle $article): bool => $article->matches($filter)));
    }
}
