<?php

namespace App\Security\Infrastructure\UI\Api\User;

use OpenApi\Attributes as OA;

class UserRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'Bioscore'
        )]
        public string $username,

        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3'
        )]
        public string $accountId,
    )
    {
        
    }
}