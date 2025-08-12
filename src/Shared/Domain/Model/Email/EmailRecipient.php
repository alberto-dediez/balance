<?php

namespace App\Shared\Domain\Model\Email;

class EmailRecipient
{
    public function __construct(
        public string $name,
        public string $email
    )
    {
        
    }

    public function __toString()
    {
        return (string) $this->email;
    }
}