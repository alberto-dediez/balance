<?php

declare(strict_types=1);

namespace App\Library\Application\Media\RunBackup;

use App\Shared\Application\CommandBus\CommandResponse;

class RunMediaBackupResponse implements CommandResponse
{
    /**
     * @param list<string> $failures
     */
    public function __construct(
        public bool $enabled,
        public int $copied,
        public int $alreadyBackedUp,
        public int $missingInSource,
        public int $purged,
        public array $failures,
    ) {}
}
