<?php

namespace App\Security\Domain\Model\RecordedEvent;

class EventNotFoundException extends \RuntimeException
{
    public function __construct(string $context, string $eventName)
    {
        parent::__construct(
            sprintf("%s.%s no pertenece a ningun evento registrado", $context, $eventName), 
            504
        );
    }
}