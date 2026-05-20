<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\UserUngrantRole;

use OpenApi\Attributes as OA;

class UserUngrantRoleRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'ROLE_OWNER',
        )]
        public string $role,
    ) {}
}
