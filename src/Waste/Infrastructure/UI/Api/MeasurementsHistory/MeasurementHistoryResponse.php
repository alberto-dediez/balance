<?php

namespace App\Waste\Infrastructure\UI\Api\MeasurementsHistory;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Waste\Infrastructure\UI\Api\MeasurementsHistory\Measurement;

class MeasurementHistoryResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'user@example.org'
        )]
        public string $username,
        
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Measurement::class))
        )]
        public array $measurements
    )
    {
        
    }
}