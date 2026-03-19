<?php

declare(strict_types=1);

namespace App\Security\Application\RecordedEvent\Register;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Event\DomainEvent;

class RegisterRecordedEventRequest implements Command
{
    public function __construct(public ?UserId $userId, public DomainEvent $event) {}
}
