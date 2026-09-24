<?php

declare(strict_types=1);

namespace App\Changelog\Domain;

use App\Shared\Domain\Event\DomainEvent as SharedDomainEvent;

abstract class DomainEvent extends SharedDomainEvent
{
    protected static string $context = 'changelog';
}
