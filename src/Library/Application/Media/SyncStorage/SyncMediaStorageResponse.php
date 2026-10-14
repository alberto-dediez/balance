<?php

declare(strict_types=1);

namespace App\Library\Application\Media\SyncStorage;

use App\Shared\Application\CommandBus\CommandResponse;

class SyncMediaStorageResponse implements CommandResponse
{
    public function __construct(
        public int $uploaded,
        public int $dropped,
        public int $failed,
        public int $pending,
        public ?string $lastError = null,
    ) {}
}
