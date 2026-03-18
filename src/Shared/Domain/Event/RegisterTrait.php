<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

trait RegisterTrait
{
    public function register(): void
    {
        DomainEventPublisher::instance()->subscribe(
            $this,
        );
    }
}
