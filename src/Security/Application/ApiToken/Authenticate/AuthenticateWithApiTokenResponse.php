<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Authenticate;

use App\Security\Domain\Model\ApiToken\ApiTokenId;

class AuthenticateWithApiTokenResponse
{
    public function __construct(public ?ApiTokenId $apiTokenId = null) {}
}
