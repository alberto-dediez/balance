<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

interface SignatureRequestRepository
{
    public function add(SignatureRequest $signatureRequest): void;

    public function remove(SignatureRequest $signatureRequest): void;

    public function find(SignatureRequestId $signatureRequestId): ?SignatureRequest;

    /** @return array<SignatureRequest> */
    public function findByFilter(SignatureRequestFilter $filter): array;

    public function totalResultsByFilter(SignatureRequestFilter $filter): int;
}
