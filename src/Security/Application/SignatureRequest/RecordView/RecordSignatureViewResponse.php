<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\RecordView;

use App\Security\Domain\Model\SignatureRequest\SignatureRequest;

class RecordSignatureViewResponse
{
    public function __construct(
        public readonly SignatureRequest $signatureRequest,
        public readonly bool $alreadySigned = false,
    ) {}
}
