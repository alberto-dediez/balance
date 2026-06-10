<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineSignatureRequestRepository;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineSignatureRequestRepositoryTest extends SignatureRequestRepositoryTest
{
    protected function createSUT(): SignatureRequestRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineSignatureRequestRepository($manager);
    }
}
