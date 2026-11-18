<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

final class InvalidConversationTitleException extends \DomainException
{
    public static function empty(): self
    {
        return new self('El título está vacío');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(sprintf('El título no puede superar los %d caracteres', $maxLength));
    }
}
