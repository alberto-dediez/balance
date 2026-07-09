<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

class InvalidProviderEmailException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf(
            'El email de contacto "%s" no es válido.',
            $email,
        ));
    }
}
