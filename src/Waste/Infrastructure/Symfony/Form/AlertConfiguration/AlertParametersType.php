<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use Symfony\Component\Form\AbstractType;

/** @extends AbstractType<mixed> */
abstract class AlertParametersType extends AbstractType
{
    /** @return array<string, mixed> */
    abstract public static function defaults(): array;
}
