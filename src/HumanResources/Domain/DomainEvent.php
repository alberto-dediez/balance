<?php

declare(strict_types=1);

namespace App\HumanResources\Domain;

use App\Shared\Domain\Event\DomainEvent as SharedDomainEvent;

abstract class DomainEvent extends SharedDomainEvent
{
    protected static string $context = 'human-resources';
}
