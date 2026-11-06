<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\Doctrine\Repository;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Infrastructure\Persistence\Doctrine\Repository\DoctrineImportationRepository;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationRepositoryTest;
use App\Tests\Unit\Shared\TemporaryDirectory;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineImportationRepositoryTest extends ImportationRepositoryTest
{
    use TemporaryDirectory;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = $this->temporaryDirectoryPath('importation-data-test');
    }

    protected function tearDown(): void
    {
        $this->removeTemporaryDirectory($this->storagePath);

        parent::tearDown();
    }

    public function testARowCanOnlyBeClaimedOnceThroughTheDataRepository(): void
    {
        $importation = ImportationMother::fromNameAndFile('file-to-import.csv', new ImportationFile(path: __DIR__ . '/../../../../Domain/Model/Importation/file-to-import.csv'));

        $repository = $this->createSUT();

        $repository->add($importation);

        $this->assertTrue($repository->claimRow($importation->id(), 0));
        $this->assertFalse($repository->claimRow($importation->id(), 0));
    }

    public function createSUT(): ImportationRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        $dataRepository = new SqliteImportationDataRepository($this->storagePath);

        return new DoctrineImportationRepository(manager: $manager, dataRepository: $dataRepository);
    }
}
