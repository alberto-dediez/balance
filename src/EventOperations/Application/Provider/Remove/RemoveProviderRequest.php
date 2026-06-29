<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Remove;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Shared\Application\CommandBus\Command;

class RemoveProviderRequest implements Command
{
    public function __construct(public ProviderId $providerId) {}
}
