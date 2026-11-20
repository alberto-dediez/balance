<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

class ContactRequestAlreadyResolvedException extends \RuntimeException
{
    public function __construct(ContactRequestId $id)
    {
        parent::__construct(
            sprintf('La solicitud de contacto %s ya está resuelta.', (string) $id),
            409,
        );
    }
}
