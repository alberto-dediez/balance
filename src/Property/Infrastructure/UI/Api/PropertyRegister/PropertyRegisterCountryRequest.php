<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;

class PropertyRegisterCountryRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: '1')]
        public ?string $externalId = null,
        #[OA\Property(type: 'string', example: 'ES')]
        public ?string $iso = null,
        #[OA\Property(type: 'string', example: 'Spain')]
        public ?string $name = null,
    ) {}
}
