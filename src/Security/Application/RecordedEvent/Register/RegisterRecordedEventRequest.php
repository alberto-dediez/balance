<?php

namespace App\Security\Application\RecordedEvent\Register;

use App\Shared\Domain\Event\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class RegisterRecordedEventRequest implements Command
{
    public function __construct(public ?UserId $userId, public DomainEvent $event)
    {
        
    }
}