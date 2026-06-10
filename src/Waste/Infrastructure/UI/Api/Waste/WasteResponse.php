<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\Waste;

use OpenApi\Attributes as OA;

class WasteResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $wasteId,
        #[OA\Property(
            type: 'string',
            example: 'Papel y cartón',
        )]
        public string $name,
        #[OA\Property(
            type: 'boolean',
            example: false,
        )]
        public bool $isDangerous,
        #[OA\Property(
            type: 'string',
            example: '1.50',
        )]
        public string $density,
        #[OA\Property(
            type: 'string',
            example: '#c3d300',
        )]
        public string $color,
    ) {}
}
