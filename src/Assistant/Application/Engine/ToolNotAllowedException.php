<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

final class ToolNotAllowedException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('La herramienta «%s» no está disponible', $name));
    }
}
