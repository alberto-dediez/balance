<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

class TwoFactorCodeInvalidException extends \DomainException
{
    public function __construct(private readonly int $attemptsLeft, private readonly bool $locked)
    {
        parent::__construct('Invalid two factor code');
    }

    public function attemptsLeft(): int
    {
        return $this->attemptsLeft;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }
}
