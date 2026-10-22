<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\Property;

use OpenApi\Attributes as OA;

class PropertyResponse
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: '8de87304-f72e-4a62-bca9-313b27d068f3',
        )]
        public string $propertyId,
        #[OA\Property(
            type: 'string',
            example: 'Hotel Abaco',
        )]
        public string $name,
        #[OA\Property(
            type: 'string',
            example: 'mycompany',
            nullable: true,
        )]
        public ?string $accountId,
        #[OA\Property(
            type: 'string',
            example: 'Hotel',
            nullable: true,
        )]
        public ?string $propertyType,
        #[OA\Property(
            type: 'string',
            example: 'Calle Mayor 1',
            nullable: true,
        )]
        public ?string $address,
        #[OA\Property(
            type: 'string',
            example: '29001',
            nullable: true,
        )]
        public ?string $postalCode,
        #[OA\Property(
            type: 'string',
            example: 'dest-123',
            nullable: true,
        )]
        public ?string $destinationId,
        #[OA\Property(
            type: 'string',
            example: 'country-123',
            nullable: true,
        )]
        public ?string $countryId,
        #[OA\Property(
            type: 'string',
            example: 'owned',
        )]
        public string $operatingRegime,
    ) {}
}
