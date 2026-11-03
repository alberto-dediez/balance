<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

final class MaterialityOverrideJustificationRequiredException extends \InvalidArgumentException
{
    public function __construct(public readonly Source $source, ?string $message = null)
    {
        parent::__construct($message ?? sprintf('Indica el motivo por el que anulas la recomendación de materialidad de %s', $source->value));
    }
}
