<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

class InvalidGuideArticleException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
