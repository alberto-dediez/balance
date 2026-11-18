<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

use App\Shared\Domain\Model\Filter;

class GuideArticleFilter
{
    use Filter;

    public function __construct(
        public GuideProfile $profile,
        public ?string $text = null,
    ) {
        $this->limit = 20;
        $this->orderBy = 'menu';
        $this->orderWay = 'ASC';
    }
}
