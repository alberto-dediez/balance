<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

class MediaNotFoundException extends \RuntimeException
{
    public function __construct(MediaId $mediaId)
    {
        parent::__construct(sprintf('Media "%s" not found', (string) $mediaId));
    }
}
