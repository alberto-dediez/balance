<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\DailyWaste;

use OpenApi\Attributes as OA;

class DailyWasteResponse
{
    public function __construct(
        #[OA\Property(type: 'string', example: '8de87304-f72e-4a62-bca9-313b27d068f3')]
        public string $dailyWasteId,
        #[OA\Property(type: 'string', example: '3fa85f64-5717-4562-b3fc-2c963f66afa6')]
        public string $propertyId,
        #[OA\Property(type: 'string', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890')]
        public string $wasteId,
        #[OA\Property(type: 'string', nullable: true, example: 'b2c3d4e5-f6a7-8901-bcde-f12345678901')]
        public ?string $wasteManagerId,
        #[OA\Property(type: 'string', example: 'waste')]
        public string $purpose,
        #[OA\Property(type: 'number', example: 0.75)]
        public float $load,
        #[OA\Property(type: 'string', example: '2025-06-04')]
        public string $day,
    ) {}
}
