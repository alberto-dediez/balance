<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Domain\DomainEvent;

abstract class InfrastructureEvent extends DomainEvent {}
