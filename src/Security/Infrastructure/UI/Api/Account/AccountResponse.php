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
        #[OA\Property(
            type: 'boolean',
            example: false,
        )]
        public bool $consultant = false,
        #[OA\Property(
            type: 'string',
            example: '3f1a1b1e-1c9d-4f7a-9c1e-2b3c4d5e6f70',
            nullable: true,
        )]
        public ?string $consultantAccountId = null,
    ) {}
}
