<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Api\AccountFind;

use OpenApi\Attributes as OA;

class FindRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'Bioscore',
        )]
        public string $name,
    ) {}
}
