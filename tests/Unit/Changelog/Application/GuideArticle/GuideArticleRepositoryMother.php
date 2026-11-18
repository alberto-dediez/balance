<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\GuideArticle;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Infrastructure\Persistence\InMemory\InMemoryGuideArticleRepository;

class GuideArticleRepositoryMother
{
    public static function withArticles(GuideArticle ...$articles): InMemoryGuideArticleRepository
    {
        $repository = new InMemoryGuideArticleRepository();

        foreach ($articles as $article) {
            $repository->add($article);
        }

        return $repository;
    }
}
