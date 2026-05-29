<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Sign;

use App\Security\Domain\Model\SignatureRequest\SignatureRequest;

class SignDocumentResponse
{
    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}
}
