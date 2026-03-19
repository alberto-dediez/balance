<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementRegister;

use OpenApi\Attributes as OA;

class MeasurementRegisterRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '123e4567-e89b-12d3-a456-426614174000',
        )]
        public string $containerId,
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
