<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Sign;

class SignDocumentRequest
{
    public function __construct(
        public string $tokenPlainValue,
        public string $acceptanceText = '',
        public string $signerIp = '',
        public string $signerUserAgent = '',
        public string $signerAcceptLanguage = '',
    ) {}
}
