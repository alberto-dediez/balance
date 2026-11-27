<?php

declare(strict_types=1);

namespace App\Library\Application\Media\RestoreBackup;

use App\Shared\Application\CommandBus\CommandResponse;

class RestoreMediaBackupResponse implements CommandResponse
{
    /**
     * @param list<string> $failures
     */
    public function __construct(
        public int $restored,
        public int $alreadyInPrimary,
        public int $missingInBackup,
        public array $failures,
    ) {}
}
