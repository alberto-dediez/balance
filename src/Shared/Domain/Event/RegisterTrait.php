<?php

namespace App\Shared\Domain\Event;

trait RegisterTrait
{
    public function register()
    {
        DomainEventPublisher::instance()->subscribe(
            $this
        );
    }
}