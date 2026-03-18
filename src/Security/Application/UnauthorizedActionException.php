<?php

declare(strict_types=1);

namespace App\Security\Application;

class UnauthorizedActionException extends \RuntimeException
{
    public function __construct(mixed $subject, Action $action)
    {
        parent::__construct(
            sprintf('La acción "%s" no puede realizarse sobre %s', $action->value, $subject instanceof \Stringable ? (string) $subject : $subject::class),
            403,
        );
    }
}
