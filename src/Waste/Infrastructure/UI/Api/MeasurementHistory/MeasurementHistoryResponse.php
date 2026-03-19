<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementHistory;

use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class MeasurementHistoryResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'user@example.org',
        )]
        public string $username,
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: MeasurementResponse::class)),
        )]
        public array $measurements,
    ) {}
}
