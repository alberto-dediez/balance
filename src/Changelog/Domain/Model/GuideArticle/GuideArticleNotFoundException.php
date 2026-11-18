<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

class GuideArticleNotFoundException extends \RuntimeException
{
    public function __construct(GuideArticlePath $path)
    {
        parent::__construct(sprintf('Guide article "%s" not found', (string) $path));
    }
}
