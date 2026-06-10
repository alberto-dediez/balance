<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Waste\Domain\Model\Alert\AlertType;

class DuplicateAlertConfigurationItemException extends \RuntimeException
{
    public function __construct(AlertType $type, ?string $propertyId, ?string $wasteId)
    {
        parent::__construct(
            sprintf(
                'Existe un item de configuración de alerta duplicado para el tipo %s (propiedad: %s, residuo: %s)',
                $type->value,
                $propertyId ?? 'todas',
                $wasteId ?? 'todos',
            ),
            409,
        );
    }
}
