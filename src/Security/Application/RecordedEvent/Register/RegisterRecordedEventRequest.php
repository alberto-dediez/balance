<?php

namespace App\Security\Application\RecordedEvent\Register;

use App\Shared\Domain\Event\DomainEvent;
use App\Security\Domain\Model\User\UserId;

class RegisterRecordedEventRequest
{
    public function __construct(public ?UserId $userId, public DomainEvent $event)
    {
        
    }
}