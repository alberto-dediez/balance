<?php

namespace App\Tests\Unit\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineRecordedEventRepository;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineRecordedEventRepositoryTest extends RecordedEventRepositoryTest
{
    public function createSUT(): RecordedEventRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineRecordedEventRepository($manager);
    }
}
