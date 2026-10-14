<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyMatch;

use OpenApi\Attributes as OA;

class PropertyMatchRequest
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'Hotel Barcelo Renacimiento',
        )]
        public string $name,
        #[OA\Property(
            type: 'string',
            example: 'hotelbeds',
            enum: ['hotelbeds', 'restel', 'bioscore'],
        )]
        public string $source,
        #[OA\Property(
            type: 'string',
            example: '12345',
        )]
        public ?string $externalId = null,
        #[OA\Property(
            type: 'string',
            example: '37.3891',
        )]
        public ?string $latitude = null,
        #[OA\Property(
            type: 'string',
            example: '-5.9845',
        )]
        public ?string $longitude = null,
        #[OA\Property(
            type: 'string',
            example: 'Calle Mayor 1',
        )]
        public ?string $address = null,
        #[OA\Property(
            type: 'string',
            example: '41001',
        )]
        public ?string $postalCode = null,
        #[OA\Property(
            type: 'string',
            example: 'Centro',
        )]
        public ?string $zone = null,
        #[OA\Property(type: PropertyMatchCountryRequest::class)]
        public ?PropertyMatchCountryRequest $country = null,
        #[OA\Property(type: PropertyMatchDestinationRequest::class)]
        public ?PropertyMatchDestinationRequest $destination = null,
        #[OA\Property(
            type: 'integer',
            example: 120,
        )]
        public ?int $rooms = null,
        #[OA\Property(
            type: 'string',
            example: 'account-uuid',
        )]
        public ?string $accountId = null,
    ) {}
}
