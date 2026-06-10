<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Verify;

use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\VerificationResult;

class VerifySignatureResponse
{
    public function __construct(
        public readonly SignatureRequest $signatureRequest,
        public readonly ?VerificationResult $verificationResult = null,
    ) {}
}
