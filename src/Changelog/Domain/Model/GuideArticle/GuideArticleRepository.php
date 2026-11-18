<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

interface GuideArticleRepository
{
    public function find(GuideProfile $profile, GuideArticlePath $path): ?GuideArticle;

    /** @return list<GuideArticle> */
    public function findByFilter(GuideArticleFilter $filter): array;

    public function totalResultsByFilter(GuideArticleFilter $filter): int;
}
