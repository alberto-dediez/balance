<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountConsultantStatus;

use OpenApi\Attributes as OA;

class AccountConsultantStatusRequest
{
    public function __construct(
        #[OA\Property(
            type: 'boolean',
            example: true,
        )]
        public bool $consultant,
    ) {}
}
