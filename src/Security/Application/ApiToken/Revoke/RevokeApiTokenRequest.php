<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Revoke;

use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Shared\Application\CommandBus\Command;

class RevokeApiTokenRequest implements Command
{
    public function __construct(public ApiTokenId $apiTokenId) {}
}
