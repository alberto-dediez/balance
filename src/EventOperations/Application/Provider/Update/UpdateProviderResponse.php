<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Update;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateProviderResponse implements CommandResponse
{
    public function __construct(
        public ProviderId $providerId,
        public string $name,
    ) {}
}
