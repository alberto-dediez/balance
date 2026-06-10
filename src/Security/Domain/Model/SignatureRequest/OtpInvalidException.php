<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class OtpInvalidException extends \DomainException
{
    public function __construct(string $reason = 'OTP inválido o expirado.')
    {
        parent::__construct($reason);
    }
}
