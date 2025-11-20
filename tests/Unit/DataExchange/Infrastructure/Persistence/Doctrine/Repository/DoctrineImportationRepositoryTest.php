<?php

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\Doctrine\Repository;

use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Infrastructure\Persistence\Doctrine\Repository\DoctrineImportationRepository;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationRepositoryTest;
use App\Tests\Unit\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineImportationRepositoryTest extends ImportationRepositoryTest
{
    public function createSUT(): ImportationRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        $dataRepository = new SqliteImportationDataRepository(SqliteImportationDataRepositoryTest::STORAGE_PATH);

        return new DoctrineImportationRepository(manager: $manager, dataRepository: $dataRepository);
    }
}
