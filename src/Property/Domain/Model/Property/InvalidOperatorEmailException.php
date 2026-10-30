<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

class InvalidOperatorEmailException extends \InvalidArgumentException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf(
            'El email de contacto "%s" no es válido.',
            $email,
        ));
    }
}
