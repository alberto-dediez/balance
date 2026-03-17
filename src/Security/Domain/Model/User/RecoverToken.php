<?php

namespace App\Security\Domain\Model\User;

use App\Shared\Domain\Uuid\UuidGenerator;

class RecoverToken implements \Stringable
{
    private const EXPIRATION_CALCULATOR = 'NOW +1 HOUR';

    public function __construct(private readonly ?string $token, private readonly ?\DateTimeImmutable $expirateAt = null)
    {

    }

    public static function generate(): RecoverToken
    {
        $token = UuidGenerator::v4();

        return new self($token, new \DateTimeImmutable(self::EXPIRATION_CALCULATOR));
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expirateAt(): ?\DateTimeImmutable
    {
        return $this->expirateAt;
    }

    public function isExpired(): bool
    {
        return null == $this->expirateAt || $this->expirateAt < new \DateTimeImmutable('NOW');
    }

    public function equals(RecoverToken $anotherToken): bool
    {
        return $this->token() === $anotherToken->token();
    }

    public function __toString(): string
    {
        return $this->token();
    }
}
