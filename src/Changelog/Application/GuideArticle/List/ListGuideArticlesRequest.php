<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\List;

class ListGuideArticlesRequest
{
    public function __construct(
        public readonly ?string $text = null,
        public readonly int $limit = 20,
    ) {}
}
