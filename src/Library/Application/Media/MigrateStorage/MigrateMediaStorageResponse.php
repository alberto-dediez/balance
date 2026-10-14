<?php

declare(strict_types=1);

namespace App\Library\Application\Media\MigrateStorage;

use App\Shared\Application\CommandBus\CommandResponse;

class MigrateMediaStorageResponse implements CommandResponse
{
    /**
     * @param list<string> $failures
     */
    public function __construct(
        public int $migrated,
        public int $alreadyInTarget,
        public int $missingInSource,
        public array $failures,
        public int $deletedFromSource,
    ) {}
}
