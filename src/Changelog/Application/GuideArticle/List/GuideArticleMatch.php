<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\List;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;

class GuideArticleMatch
{
    public function __construct(
        public readonly GuideArticle $article,
        public readonly ?string $excerpt,
    ) {}
}
