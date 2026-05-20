<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\UserGrantRole;

use OpenApi\Attributes as OA;

class UserGrantRoleRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'ROLE_OWNER',
        )]
        public string $role,
    ) {}
}
