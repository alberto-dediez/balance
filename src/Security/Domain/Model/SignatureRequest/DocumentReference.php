<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class DocumentReference
{
    public function __construct(
        private readonly ?string $sha256 = null,
        private readonly ?int $size = null,
        private readonly ?string $filename = null,
        private readonly ?string $path = null,
    ) {}

    public static function fromPath(string $path): self
    {
        if (!is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not readable', $path));
        }

        $sha256 = hash_file('sha256', $path);
        $size = filesize($path);
        $filename = basename($path);

        assert(false !== $sha256);
        assert(false !== $size);

        return new self(
            sha256: $sha256,
            size: $size,
            filename: $filename ?: null,
            path: $path,
        );
    }

    public function sha256(): ?string
    {
        return $this->sha256;
    }

    public function size(): ?int
    {
        return $this->size;
    }

    public function filename(): ?string
    {
        return $this->filename;
    }

    public function path(): ?string
    {
        return $this->path;
    }
}
