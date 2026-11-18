<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

final class InvalidMessageException extends \DomainException
{
    public static function empty(): self
    {
        return new self('El mensaje está vacío');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(sprintf('El mensaje no puede superar los %d caracteres', $maxLength));
    }

    public static function tooManyAttachments(): self
    {
        return new self(sprintf('No se pueden adjuntar más de %d ficheros', Message::ATTACHMENTS_MAX_COUNT));
    }

    public static function unreadableAttachment(string $fileName): self
    {
        return new self(sprintf('El fichero «%s» no es de un tipo legible', $fileName));
    }

    public static function attachmentTooLarge(string $fileName): self
    {
        return new self(sprintf('El fichero «%s» supera el tamaño máximo', $fileName));
    }

    public static function emptyAttachment(string $fileName): self
    {
        return new self(sprintf('El fichero «%s» está vacío', $fileName));
    }

    public static function imageTooLarge(string $fileName, int $maxBytes): self
    {
        return new self(sprintf('La imagen «%s» supera el tamaño máximo de %d MB', $fileName, intdiv($maxBytes, 1048576)));
    }

    public static function notUtf8Attachment(string $fileName): self
    {
        return new self(sprintf('El fichero «%s» no está codificado en UTF-8; guárdalo como UTF-8 y vuelve a adjuntarlo', $fileName));
    }

    public static function attachmentsTooLarge(): self
    {
        return new self('Los ficheros adjuntos superan en conjunto el tamaño máximo de un mensaje');
    }

    public static function notFound(MessageId $id): self
    {
        return new self(sprintf('Message %s not found', $id));
    }

    public static function notPending(MessageId $id): self
    {
        return new self(sprintf('Message %s is not pending', $id));
    }
}
