<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media;

use App\Library\Application\Media\MediaAuthorizer;

class MediaAuthorizerMother
{
    public static function create(): MediaAuthorizer
    {
        return new MediaAuthorizer();
    }
}
