<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\Property\Property;

class PropertyWithoutAccountException extends \RuntimeException
{
    public function __construct(Property $property)
    {
        parent::__construct(
            sprintf(
                'La propiedad %s (%s) no tiene cuenta asignada: asígnale una cuenta antes de adherirla a un distribuidor.',
                $property->name(),
                (string) $property->id(),
            ),
            409,
        );
    }
}
