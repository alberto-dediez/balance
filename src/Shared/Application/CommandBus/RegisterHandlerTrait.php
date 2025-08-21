<?php

namespace App\Shared\Application\CommandBus;

trait RegisterHandlerTrait
{
    public function register()
    {
        CommandBusDispatcher::instance()->subscribe(
            $this
        );
    }
}