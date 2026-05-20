<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;

class PropertyRegisterRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'Hotel Abaco',
        )]
        public string $name,
        #[OA\Property(
            type: 'string',
            example: 'mycompany',
        )]
        public string $accountId,
    ) {}
}
