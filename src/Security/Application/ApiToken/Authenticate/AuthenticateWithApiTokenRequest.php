<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Authenticate;

class AuthenticateWithApiTokenRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public string $plainValue,
    ) {}
}
