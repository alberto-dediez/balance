<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;

class PropertyRegisterRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: 'Hotel Abaco')]
        public string $name,
        #[OA\Property(type: 'string', example: 'mycompany')]
        public string $accountId,
        // PropertyInfo fields
        #[OA\Property(type: 'string', example: 'Hotel', nullable: true)]
        public ?string $propertyType = null,
        #[OA\Property(type: 'string', example: null, nullable: true)]
        public ?string $categoryId = null,
        #[OA\Property(type: 'string', example: null, nullable: true)]
        public ?string $brandId = null,
        #[OA\Property(type: 'string', example: 'Calle Mayor 1', nullable: true)]
        public ?string $address = null,
        #[OA\Property(type: 'string', example: '28001', nullable: true)]
        public ?string $postalCode = null,
        #[OA\Property(type: 'string', example: '-3.703790', nullable: true)]
        public ?string $longitude = null,
        #[OA\Property(type: 'string', example: '40.416775', nullable: true)]
        public ?string $latitude = null,
        #[OA\Property(type: 'integer', example: 120, nullable: true)]
        public ?int $rooms = null,
        #[OA\Property(type: 'string', example: 'Centro', nullable: true)]
        public ?string $zone = null,
        #[OA\Property(type: 'string', example: 'https://hotelabaco.com', nullable: true)]
        public ?string $web = null,
        #[OA\Property(type: 'string', example: null, nullable: true)]
        public ?string $destinationId = null,
        #[OA\Property(type: 'string', example: null, nullable: true)]
        public ?string $countryId = null,
        // ExternalId fields
        #[OA\Property(type: 'string', example: 'hotelbeds', nullable: true, enum: ['hotelbeds', 'restel', 'bioscore'])]
        public ?string $source = null,
        #[OA\Property(type: 'string', example: null, nullable: true)]
        public ?string $externalId = null,
    ) {}
}
