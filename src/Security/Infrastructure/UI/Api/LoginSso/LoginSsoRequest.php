<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\LoginSso;

use OpenApi\Attributes as OA;

class LoginSsoRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'user@example.com',
        )]
        public string $username,
        #[OA\Property(
            type: 'string',
            example: 'eyJhbGciOiJSUzI1NiJ9...',
        )]
        public string $token,
    ) {}
}
