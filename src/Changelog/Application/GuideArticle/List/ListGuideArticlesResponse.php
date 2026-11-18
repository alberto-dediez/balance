<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\List;

class ListGuideArticlesResponse
{
    /**
     * @param list<GuideArticleMatch> $matches
     */
    public function __construct(
        public readonly array $matches = [],
        public readonly int $total = 0,
    ) {}
}
