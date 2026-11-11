<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Revoke;

use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Shared\Application\CommandBus\CommandResponse;

class RevokeApiTokenResponse implements CommandResponse
{
    public function __construct(public ApiTokenId $apiTokenId) {}
}
