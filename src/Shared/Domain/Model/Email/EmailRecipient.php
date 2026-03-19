<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Email;

class EmailRecipient implements \Stringable
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    public function __toString(): string
    {
        return $this->email;
    }
}
