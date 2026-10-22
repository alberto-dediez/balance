<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

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
        #[OA\Property(type: PropertyRegisterCategoryRequest::class)]
        public ?PropertyRegisterCategoryRequest $category = null,
        #[OA\Property(type: PropertyRegisterBrandRequest::class)]
        public ?PropertyRegisterBrandRequest $brand = null,
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
        #[OA\Property(type: PropertyRegisterDestinationRequest::class)]
        public ?PropertyRegisterDestinationRequest $destination = null,
        #[OA\Property(type: PropertyRegisterCountryRequest::class)]
        public ?PropertyRegisterCountryRequest $country = null,
        // ExternalId fields
        #[OA\Property(type: 'string', example: 'hotelbeds', nullable: true, enum: ['hotelbeds', 'restel', 'bioscore'])]
        public ?string $source = null,
        #[OA\Property(type: 'string', example: null, nullable: true)]
        public ?string $externalId = null,
        #[OA\Property(type: 'string', example: 'franchised', nullable: true, enum: ['owned', 'leased', 'managed', 'franchised', 'leased_out'])]
        #[Assert\Choice(choices: ['owned', 'leased', 'managed', 'franchised', 'leased_out'])]
        public ?string $operatingRegime = null,
    ) {}
}
