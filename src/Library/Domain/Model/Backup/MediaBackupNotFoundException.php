<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Backup;

class MediaBackupNotFoundException extends \DomainException
{
    public function __construct(string $mediaId)
    {
        parent::__construct(sprintf('There is no backup of media "%s"', $mediaId));
    }
}
