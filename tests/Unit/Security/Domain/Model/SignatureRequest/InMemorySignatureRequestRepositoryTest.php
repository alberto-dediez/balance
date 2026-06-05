<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;

class InMemorySignatureRequestRepositoryTest extends SignatureRequestRepositoryTest
{
    protected function createSUT(): SignatureRequestRepository
    {
        return SignatureRequestRepositoryMother::inMemory();
    }
}
