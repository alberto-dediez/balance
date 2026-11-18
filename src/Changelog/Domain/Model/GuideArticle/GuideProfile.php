<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

enum GuideProfile: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case User = 'user';
}
