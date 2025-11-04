<?php

namespace App\Waste\Infrastructure\UI\Api\Measurement;

use OpenApi\Attributes as OA;

class MeasurementResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3'
        )]
        public string $measurementId,

        #[OA\Property(
            type: 'string',
            example: 'Hotel Abaco'
        )]
        public string $property,

        #[OA\Property(
            type: 'string',
            example: 'Papel y carton'
        )]
        public string $waste,

        #[OA\Property(
            type: 'string',
            example: 'Sadeco'
        )]
        public string $wasteManager,

        #[OA\Property(
            type: 'string',
            example: 1
        )]
        public int $quantity,

        #[OA\Property(
            type: 'string',
            example: '50'
        )]
        public string $load,

        #[OA\Property(
            type: 'string',
            example: '%'
        )]
        public string $unit,

        #[OA\Property(
            type: 'string',
            example: "Desecho"
        )]
        public string $purpose,

        #[OA\Property(
            type: 'string',
            example: 'Renovación de la planta superior'
        )]
        public string $justification,

        #[OA\Property(
            type: 'string',
            example: "2025-06-10 15:12:04"
        )]
        public string $registeredAt
    )
    {

    }
}
