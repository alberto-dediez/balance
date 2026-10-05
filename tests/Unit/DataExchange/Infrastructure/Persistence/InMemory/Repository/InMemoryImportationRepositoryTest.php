<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\InMemory\Repository;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
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
