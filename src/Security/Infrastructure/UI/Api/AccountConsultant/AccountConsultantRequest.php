<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountConsultant;

use OpenApi\Attributes as OA;

class AccountConsultantRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '3f1a1b1e-1c9d-4f7a-9c1e-2b3c4d5e6f70',
            nullable: true,
        )]
        public ?string $consultantAccountId = null,
    ) {}
}
