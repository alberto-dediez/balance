<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class VerificationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly string $details = '',
    ) {}
}
