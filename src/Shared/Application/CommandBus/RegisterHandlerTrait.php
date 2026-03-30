<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

trait RegisterHandlerTrait
{
    public function register(): void
    {
        CommandBusDispatcher::instance()->subscribe(
            $this,
        );
    }
}
