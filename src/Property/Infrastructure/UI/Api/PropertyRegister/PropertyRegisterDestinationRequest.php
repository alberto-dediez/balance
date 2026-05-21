<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;

class PropertyRegisterDestinationRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: '10')]
        public ?string $externalId = null,
        #[OA\Property(type: 'string', example: 'Seville')]
        public ?string $name = null,
    ) {}
}
