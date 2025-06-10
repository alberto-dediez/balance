<?php

namespace App\Waste\Infrastructure\UI\Api\MeasurementsHistory;

use OpenApi\Attributes as OA;

class Measurement
{
    public function __construct(
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
            example: '50 %'
        )]
        public string $load,

        #[OA\Property(
            type: 'string',
            example: "Deshecho"
        )]
        public string $purpose,

        #[OA\Property(
            type: 'string',
            example: "2025-06-10 15:12:04"
        )]
        public string $registeredAt
    )
    {
        
    }
}