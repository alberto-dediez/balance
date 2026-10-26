<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

final class InvalidUsernameException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('El nombre de usuario no puede contener los caracteres < ni >');
    }
}
