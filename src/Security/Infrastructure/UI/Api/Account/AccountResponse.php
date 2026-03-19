<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\Account;

use OpenApi\Attributes as OA;

class AccountResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $accountId,
        #[OA\Property(
            type: 'string',
            example: 'Bioscore',
        )]
        public string $name,
        #[OA\Property(
            type: 'boolean',
            example: true,
        )]
        public bool $active,
    ) {}
}
