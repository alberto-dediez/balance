<?php

namespace App\Shared\Application\CommandBus;

trait RegisterHandlerTrait
{
    public function register(): void
    {
        CommandBusDispatcher::instance()->subscribe(
            $this
        );
    }
}