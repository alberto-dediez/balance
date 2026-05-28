<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\UserUngrantRole;

use OpenApi\Attributes as OA;

class UserUngrantRoleResponse
{
    /** @param string[] $roles */
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
            type: 'array',
            items: new OA\Items(type: 'string', example: 'ROLE_USER'),
        )]
        public array $roles,
    ) {}
}
