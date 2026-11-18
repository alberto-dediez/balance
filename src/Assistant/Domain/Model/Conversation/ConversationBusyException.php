<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

final class ConversationBusyException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('El asistente sigue trabajando en la petición anterior');
    }
}
