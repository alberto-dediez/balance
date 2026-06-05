<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemorySignatureRequestRepository;

class SignatureRequestRepositoryMother
{
    public static function inMemory(): InMemorySignatureRequestRepository
    {
        return new InMemorySignatureRequestRepository();
    }
}
