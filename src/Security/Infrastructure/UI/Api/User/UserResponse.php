<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\User;

use OpenApi\Attributes as OA;

class UserResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $userId,
        #[OA\Property(
            type: 'string',
            example: 'user@dediez.software',
        )]
        public string $username,
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $accountId,
    ) {}
}
