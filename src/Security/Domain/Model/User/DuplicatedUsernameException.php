<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

class DuplicatedUsernameException extends \RuntimeException
{
    public function __construct(string $username)
    {
        parent::__construct(
            sprintf('El username %s ya está registrado', $username),
            500,
        );
    }
}
