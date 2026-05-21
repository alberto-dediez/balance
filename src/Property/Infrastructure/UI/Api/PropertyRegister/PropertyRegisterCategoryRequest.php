<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;

class PropertyRegisterCategoryRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: '5')]
        public ?string $externalId = null,
        #[OA\Property(type: 'string', example: 'Resort')]
        public ?string $name = null,
    ) {}
}
