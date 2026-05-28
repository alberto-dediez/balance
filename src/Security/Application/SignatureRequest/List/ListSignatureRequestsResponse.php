<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\List;

class ListSignatureRequestsResponse
{
    /** @param array<\App\Security\Domain\Model\SignatureRequest\SignatureRequest> $signatureRequests */
    public function __construct(public array $signatureRequests = [], public int $totalResults = 0) {}
}
