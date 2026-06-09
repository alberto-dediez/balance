<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

class StoredFile
{
    public function __construct(
        private readonly ?string $path = null,
        private readonly ?string $extension = null,
    ) {}

    public function path(): ?string
    {
        return $this->path;
    }

    public function extension(): ?string
    {
        return $this->extension;
    }

    public function isEmpty(): bool
    {
        return null === $this->path;
    }
}
