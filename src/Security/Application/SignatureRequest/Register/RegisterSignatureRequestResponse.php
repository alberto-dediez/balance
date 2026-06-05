<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Register;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterSignatureRequestResponse implements CommandResponse
{
    public function __construct(
        public SignatureRequestId $signatureRequestId,
        public string $tokenPlainValue,
        public SignatureRequestStatus $status,
    ) {}
}
