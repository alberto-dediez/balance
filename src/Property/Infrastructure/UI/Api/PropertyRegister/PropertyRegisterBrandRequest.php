<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;

class PropertyRegisterBrandRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: '3')]
        public ?string $externalId = null,
        #[OA\Property(type: 'string', example: 'Meliá')]
        public ?string $name = null,
    ) {}
}
