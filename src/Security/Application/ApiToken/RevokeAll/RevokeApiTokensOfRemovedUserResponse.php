<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\RevokeAll;

use App\Shared\Application\CommandBus\CommandResponse;

class RevokeApiTokensOfRemovedUserResponse implements CommandResponse
{
    public function __construct(public int $revoked) {}
}
