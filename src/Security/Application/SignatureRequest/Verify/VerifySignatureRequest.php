<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Verify;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;

class VerifySignatureRequest
{
    public function __construct(
        public SignatureRequestId $signatureRequestId,
    ) {}
}
