<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class SignatureRequestNotPendingException extends \RuntimeException
{
    public function __construct(SignatureRequestId $id)
    {
        parent::__construct(sprintf('La solicitud %s no está en estado pendiente.', (string) $id), 409);
    }
}
