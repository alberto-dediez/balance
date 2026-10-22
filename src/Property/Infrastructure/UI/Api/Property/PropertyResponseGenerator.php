<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\Property;

use App\Property\Domain\Model\Property\Property as ModelProperty;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponse as ApiProperty;

class PropertyResponseGenerator
{
    public function generate(ModelProperty $model): ApiProperty
    {
        $info = $model->info();

        return new ApiProperty(
            propertyId: (string) $model->id(),
            name: $model->name(),
            accountId: null !== $model->accountId() ? (string) $model->accountId() : null,
            propertyType: $info->propertyType()?->value,
            address: $info->address(),
            postalCode: $info->postalCode(),
            destinationId: null !== $info->destinationId() ? (string) $info->destinationId() : null,
            countryId: null !== $info->countryId() ? (string) $info->countryId() : null,
            operatingRegime: $model->operatingRegime()->value,
        );
    }
}
