<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\View;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;

class ViewGuideArticleResponse
{
    public function __construct(
        public readonly GuideArticle $article,
    ) {}
}
