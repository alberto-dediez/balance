<?php

namespace App\Waste\Infrastructure\UI\Api\Container;

use OpenApi\Attributes as OA;

class ContainerResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3'
        )]
        public string $containerId,

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
            example: '%'
        )]
        public string $unit,

        #[OA\Property(
            type: 'string',
            example: "Desecho"
        )]
        public string $purpose
    )
    {

    }
}
