<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;

trait GetSignatureRequestFromIdTrait
{
    private function getSignatureRequestFromId(string $signatureRequestId, SignatureRequestRepository $repository): SignatureRequest
    {
        $signatureRequest = $repository->find(new SignatureRequestId($signatureRequestId));

        if (null == $signatureRequest) {
            throw $this->createNotFoundException('Solicitud de firma no encontrada');
        }

        return $signatureRequest;
    }
}
