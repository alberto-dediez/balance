<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\StorageSync;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class StorageSyncTask
{
    private const int MAX_ERROR_LENGTH = 1000;

    public function __construct(
        private readonly string $path,
        private readonly \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private int $attempts = 0,
        private ?\DateTimeImmutable $lastAttemptAt = null,
        private ?string $lastError = null,
    ) {}

    public function path(): string
    {
        return $this->path;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function lastAttemptAt(): ?\DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function recordFailure(string $error, \DateTimeImmutable $now): void
    {
        ++$this->attempts;
        $this->lastAttemptAt = $now;
        $this->lastError = mb_substr($error, 0, self::MAX_ERROR_LENGTH);
    }
}
