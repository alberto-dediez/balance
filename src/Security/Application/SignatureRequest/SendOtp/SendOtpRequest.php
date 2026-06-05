<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\SendOtp;

class SendOtpRequest
{
    public function __construct(
        public string $tokenPlainValue,
        public string $signerIp = '',
        public string $signerUserAgent = '',
    ) {}
}
