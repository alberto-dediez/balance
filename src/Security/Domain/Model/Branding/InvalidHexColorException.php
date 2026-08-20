<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

class InvalidHexColorException extends \DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('El color "%s" no es un hexadecimal válido de 6 dígitos.', $value));
    }
}
