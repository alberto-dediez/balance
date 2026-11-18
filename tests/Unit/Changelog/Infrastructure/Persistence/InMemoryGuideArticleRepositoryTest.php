<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence;

use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Changelog\Infrastructure\Persistence\InMemory\InMemoryGuideArticleRepository;

class InMemoryGuideArticleRepositoryTest extends GuideArticleRepositoryTest
{
    protected function givenArticles(array $articles): GuideArticleRepository
    {
        $repository = new InMemoryGuideArticleRepository();

        foreach ($articles as $article) {
            $repository->add($article);
        }

        return $repository;
    }
}
