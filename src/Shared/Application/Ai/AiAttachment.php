<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

final readonly class AiAttachment
{
    private const string PDF_MIME_TYPE = 'application/pdf';

    private const array IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(
        public string $name,
        public string $mimeType,
        public string $contents,
    ) {}

    public static function fromFile(string $path, string $mimeType, string $name): self
    {
        $contents = @file_get_contents($path);

        if (false === $contents) {
            throw new \RuntimeException(sprintf('The attachment "%s" cannot be read', $name));
        }

        return new self($name, $mimeType, $contents);
    }

    public function isPdf(): bool
    {
        return self::PDF_MIME_TYPE === $this->mimeType;
    }

    public function isImage(): bool
    {
        return in_array($this->mimeType, self::IMAGE_MIME_TYPES, true);
    }

    public function size(): int
    {
        return strlen($this->contents);
    }
}
