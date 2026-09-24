<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

class InvalidYouTubeUrlException extends \InvalidArgumentException
{
    public function __construct(string $url)
    {
        parent::__construct(sprintf('"%s" is not a valid YouTube video URL', $url));
    }
}
