<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountConsultantStatus;

use OpenApi\Attributes as OA;

class AccountConsultantStatusResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $accountId,
        #[OA\Property(
            type: 'boolean',
            example: true,
        )]
        public bool $consultant,
    ) {}
}
