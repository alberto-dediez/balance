<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\DailyWasteList;

use OpenApi\Attributes as OA;

class DailyWasteListRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: 'mycompany')]
        public ?string $accountId = null,
        #[OA\Property(type: 'string', example: 'property-uuid')]
        public ?string $propertyId = null,
        #[OA\Property(type: 'string', format: 'date', example: '2024-01-01')]
        public ?string $dayFrom = null,
        #[OA\Property(type: 'string', format: 'date', example: '2024-12-31')]
        public ?string $dayTo = null,
    ) {}
}
