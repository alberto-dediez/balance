<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyMatch;

use OpenApi\Attributes as OA;

class PropertyMatchResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            nullable: true,
        )]
        public ?string $propertyId,
    ) {}
}
