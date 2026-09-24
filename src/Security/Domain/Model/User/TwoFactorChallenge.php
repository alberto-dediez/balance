<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

final class TwoFactorChallenge
{
    private const HKDF_INFO = 'two-factor-challenge';
    private const HKDF_LENGTH = 32;
    private const MAX_ATTEMPTS = 3;
    private const EXPIRY_MINUTES = 10;

    public function __construct(
        private readonly ?string $codeHash = null,
        private readonly ?\DateTimeImmutable $expiresAt = null,
        private readonly ?int $attempts = 0,
        #[\SensitiveParameter]
        private readonly string $plainCode = '',
    ) {}

    public static function generate(#[\SensitiveParameter] string $appSecret): self
    {
        $code = (string) random_int(100000, 999999);

        return new self(
            codeHash: self::computeHash($code, $appSecret),
            expiresAt: new \DateTimeImmutable('+' . self::EXPIRY_MINUTES . ' minutes'),
            attempts: 0,
            plainCode: $code,
        );
    }

    public static function withoutCode(): self
    {
        return new self(
            codeHash: null,
            expiresAt: new \DateTimeImmutable('+' . self::EXPIRY_MINUTES . ' minutes'),
            attempts: 0,
        );
    }

    public static function none(): self
    {
        return new self();
    }

    public function plainCode(): string
    {
        return $this->plainCode;
    }

    public function verify(string $code, #[\SensitiveParameter] string $appSecret): bool
    {
        if ($this->isExpired() || $this->isLocked() || null === $this->codeHash) {
            return false;
        }

        return hash_equals($this->codeHash, self::computeHash($code, $appSecret));
    }

    public function isPending(): bool
    {
        return null !== $this->expiresAt && !$this->isExpired() && !$this->isLocked();
    }

    public function isExpired(): bool
    {
        return null === $this->expiresAt || $this->expiresAt < new \DateTimeImmutable('NOW');
    }

    public function isLocked(): bool
    {
        return ($this->attempts ?? 0) >= self::MAX_ATTEMPTS;
    }

    public function attemptsLeft(): int
    {
        return max(0, self::MAX_ATTEMPTS - ($this->attempts ?? 0));
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function codeHash(): ?string
    {
        return $this->codeHash;
    }

    public function withFailedAttempt(): self
    {
        return new self(
            codeHash: $this->codeHash,
            expiresAt: $this->expiresAt,
            attempts: ($this->attempts ?? 0) + 1,
            plainCode: '',
        );
    }

    private static function computeHash(string $code, #[\SensitiveParameter] string $appSecret): string
    {
        $derivedKey = hash_hkdf('sha256', $appSecret, self::HKDF_LENGTH, self::HKDF_INFO);

        return hash_hmac('sha256', $code, $derivedKey);
    }
}
