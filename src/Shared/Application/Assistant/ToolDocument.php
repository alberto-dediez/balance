<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

use App\Shared\Application\Ai\AiAttachment;

final readonly class ToolDocument
{
    public const array TEXT_MIME_TYPES = ['text/markdown', 'text/plain', 'text/csv', 'text/html', 'application/json'];
    public const int IMAGE_MAX_BYTES = 5242880;

    private function __construct(
        public ?string $text,
        public bool $truncated,
        public ?AiAttachment $attachment,
    ) {}

    public static function isReadable(string $mimeType): bool
    {
        return in_array($mimeType, self::TEXT_MIME_TYPES, true) || 'application/pdf' === $mimeType || (new AiAttachment('', $mimeType, ''))->isImage();
    }

    public static function isText(string $mimeType): bool
    {
        return in_array($mimeType, self::TEXT_MIME_TYPES, true);
    }

    public static function isImage(string $mimeType): bool
    {
        return (new AiAttachment('', $mimeType, ''))->isImage();
    }

    public static function from(string $name, string $mimeType, string $contents, int $maxBytes): self
    {
        if (!self::isReadable($mimeType)) {
            throw InvalidToolArgumentsException::because(sprintf('El fichero «%s» no es de un tipo legible', $name));
        }

        if (strlen($contents) > $maxBytes) {
            throw InvalidToolArgumentsException::because(sprintf('El fichero «%s» supera el tamaño máximo', $name));
        }

        if (in_array($mimeType, self::TEXT_MIME_TYPES, true)) {
            [$text, $truncated] = ToolResult::truncate($contents);

            return new self($text, $truncated, null);
        }

        return new self(null, false, new AiAttachment($name, $mimeType, $contents));
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        if (null !== $this->attachment) {
            return ['attached' => true];
        }

        return ['contents' => $this->text, 'truncated' => $this->truncated];
    }

    /** @return list<AiAttachment> */
    public function attachments(): array
    {
        return null === $this->attachment ? [] : [$this->attachment];
    }
}
