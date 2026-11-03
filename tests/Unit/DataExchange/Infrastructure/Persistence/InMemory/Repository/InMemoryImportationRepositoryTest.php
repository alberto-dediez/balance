<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\InMemory\Repository;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationStatus;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use PHPUnit\Framework\TestCase;

class InMemoryImportationRepositoryTest extends TestCase
{
    private const STORAGE_PATH = __DIR__ . '/../../../../../../../var/sqlite-test-injected/';

    public function testGivenAnInjectedDataRepositoryWhenIAddAnImportationThenItsDataIsStoredThroughIt(): void
    {
        $importation = ImportationMother::fromNameAndFile(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertFileExists(self::STORAGE_PATH . $importation->id() . '.sqlite');
        $this->assertEquals(22, $repository->pendingRows($importation->id()));

        $repository->remove($importation);

        $this->assertFileDoesNotExist(self::STORAGE_PATH . $importation->id() . '.sqlite');
    }

    public function testTheExecutionOfAnImportationCanOnlyBeClaimedOnce(): void
    {
        $importation = ImportationMother::fromNameAndFile(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertTrue($repository->claimExecution($importation->id()));
        $this->assertFalse($repository->claimExecution($importation->id()));
    }

    public function testTheExecutionOfAnAlreadyExecutedImportationCannotBeClaimed(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
            ImportationStatus::Executed,
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertFalse($repository->claimExecution($importation->id()));
    }

    public function testTheStartOfAnImportationCanOnlyBeClaimedOnce(): void
    {
        $importation = ImportationMother::fromNameAndFile(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertTrue($repository->claimStart($importation->id()));
        $this->assertFalse($repository->claimStart($importation->id()));
    }

    public function testTheStartOfAnImportationInProgressCannotBeClaimed(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
            ImportationStatus::InProgress,
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertFalse($repository->claimStart($importation->id()));
    }

    public function testTheStartOfAnExecutedImportationCannotBeClaimed(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
            ImportationStatus::Executed,
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertFalse($repository->claimStart($importation->id()));
    }

    public function testARowCanOnlyBeClaimedOnceThroughTheDataRepository(): void
    {
        $importation = ImportationMother::fromNameAndFile(
            'superheroes.csv',
            new ImportationFile(path: __DIR__ . '/../../PDO/Repository/file-to-import.csv', offset: 0),
        );

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository(self::STORAGE_PATH));
        $repository->add($importation);

        $this->assertTrue($repository->claimRow($importation->id(), 0));
        $this->assertFalse($repository->claimRow($importation->id(), 0));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if (!is_dir(self::STORAGE_PATH)) {
            return;
        }

        foreach (glob(self::STORAGE_PATH . '*') ?: [] as $file) {
            unlink($file);
        }

        rmdir(self::STORAGE_PATH);
    }
}
