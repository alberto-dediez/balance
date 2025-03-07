<?php

namespace App\Security\Application\User\Register;

class DuplicateUsernameException extends \RuntimeException
{
    public function __construct(string $username)
    {
        parent::__construct(sprintf("Ya existe un usuario registrado con el nombre de usuario %s", $username));
    }
}