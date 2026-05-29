<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

interface SignatureFileStorage
{
    public function getSignedDocumentPath(SignatureRequestId $id): string;
}
