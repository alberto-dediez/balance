<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class OtpChallenge
{
    private const HKDF_INFO = 'otp-challenge';
    private const HKDF_LENGTH = 32;
    private const MAX_ATTEMPTS = 3;
    private const EXPIRY_MINUTES = 15;

    public function __construct(
        private readonly ?string $codeHash = null,
        private readonly ?\DateTimeImmutable $expiresAt = null,
        private readonly ?int $attempts = 0,
        private readonly ?\DateTimeImmutable $verifiedAt = null,
        #[\SensitiveParameter]
        private readonly string $plainCode = '',
    ) {}

    public static function generate(#[\SensitiveParameter] string $appSecret): self
    {
        $code = (string) random_int(100000, 999999);
        $codeHash = self::computeHash($code, $appSecret);

        return new self(
            codeHash: $codeHash,
            expiresAt: new \DateTimeImmutable('+' . self::EXPIRY_MINUTES . ' minutes'),
            attempts: 0,
            verifiedAt: null,
            plainCode: $code,
        );
    }

    public function plainCode(): string
    {
        return $this->plainCode;
    }

    public function verify(string $code, #[\SensitiveParameter] string $appSecret): bool
    {
        if ($this->isExpired() || $this->isLocked() || $this->isVerified()) {
            return false;
        }

        return hash_equals($this->codeHash ?? '', self::computeHash($code, $appSecret));
    }

    public function isExpired(): bool
    {
        return null === $this->expiresAt || $this->expiresAt < new \DateTimeImmutable('NOW');
    }

    public function isVerified(): bool
    {
        return null !== $this->verifiedAt;
    }

    public function isLocked(): bool
    {
        return ($this->attempts ?? 0) >= self::MAX_ATTEMPTS;
    }

    public function attempts(): int
    {
        return $this->attempts ?? 0;
    }

    public function attemptsLeft(): int
    {
        return max(0, self::MAX_ATTEMPTS - ($this->attempts ?? 0));
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function verifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
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
            verifiedAt: $this->verifiedAt,
            plainCode: '',
        );
    }

    public function withVerified(\DateTimeImmutable $at): self
    {
        return new self(
            codeHash: $this->codeHash,
            expiresAt: $this->expiresAt,
            attempts: $this->attempts,
            verifiedAt: $at,
            plainCode: '',
        );
    }

    private static function computeHash(string $code, #[\SensitiveParameter] string $appSecret): string
    {
        $derivedKey = hash_hkdf('sha256', $appSecret, self::HKDF_LENGTH, self::HKDF_INFO);

        return hash_hmac('sha256', $code, $derivedKey);
    }
}
