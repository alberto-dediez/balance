<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

class InvalidFontFamilyNameException extends \DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('La tipografía "%s" no es un nombre de familia válido.', $value));
    }
}
