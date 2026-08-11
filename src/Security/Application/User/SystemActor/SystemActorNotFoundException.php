<?php

declare(strict_types=1);

namespace App\Security\Application\User\SystemActor;

class SystemActorNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No user is available to perform system-driven work.');
    }
}
