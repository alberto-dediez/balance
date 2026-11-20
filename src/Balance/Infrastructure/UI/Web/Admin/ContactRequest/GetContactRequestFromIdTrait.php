<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;

trait GetContactRequestFromIdTrait
{
    private function getContactRequestFromId(string $contactRequestId, ContactRequestRepository $repository): ContactRequest
    {
        $contactRequest = $repository->find(new ContactRequestId($contactRequestId));

        if (null === $contactRequest) {
            throw $this->createNotFoundException('Solicitud de contacto no encontrada');
        }

        return $contactRequest;
    }
}
