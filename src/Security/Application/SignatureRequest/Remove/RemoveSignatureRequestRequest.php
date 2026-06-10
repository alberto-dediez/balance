<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Remove;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Application\CommandBus\Command;

class RemoveSignatureRequestRequest implements Command
{
    public function __construct(public SignatureRequestId $signatureRequestId) {}
}
