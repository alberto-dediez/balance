<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\LinkUser;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class LinkProviderUserRequest implements Command
{
    public function __construct(
        public ProviderId $providerId,
        public UserId $userId,
    ) {}
}
