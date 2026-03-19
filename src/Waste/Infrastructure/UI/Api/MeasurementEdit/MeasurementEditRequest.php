<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementEdit;

use OpenApi\Attributes as OA;

class MeasurementEditRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 1,
        )]
        public int $quantity,
        #[OA\Property(
            type: 'string',
            example: '50',
        )]
        public string $load,
        #[OA\Property(
            type: 'string',
            example: 'Renovación de la planta superior',
        )]
        public string $justification,
    ) {}
}
