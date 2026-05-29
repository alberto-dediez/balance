<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\VerifyOtp;

class VerifyOtpRequest
{
    public function __construct(
        public string $tokenPlainValue,
        public string $otpCode,
        public string $signerIp = '',
    ) {}
}
