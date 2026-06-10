<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class SignatureAlreadySignedException extends \RuntimeException
{
    public function __construct(SignatureRequestId $id)
    {
        parent::__construct(sprintf('La solicitud %s ya ha sido firmada.', (string) $id), 409);
    }
}
