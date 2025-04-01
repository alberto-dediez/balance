<?php

namespace App\Security\Application;

use Stringable;

class UnauthorizedActionException extends \RuntimeException
{
    public function __construct(mixed $subject, Action $action)
    {
        parent::__construct(
            sprintf("La acción \"%s\" no puede realizarse sobre %s", $action->value, $subject instanceof Stringable ? (string) $subject : get_class($subject)), 
            403
        );
    }
}