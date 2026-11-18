<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\View;

use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;

class ViewGuideArticleRequest
{
    public function __construct(
        public readonly GuideArticlePath $path,
    ) {}
}
