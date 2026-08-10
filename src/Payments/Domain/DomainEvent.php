<?php

declare(strict_types=1);

namespace App\Payments\Domain;

use App\Shared\Domain\Event\DomainEvent as BaseDomainEvent;

abstract class DomainEvent extends BaseDomainEvent
{
    protected static string $context = 'payments';
}
