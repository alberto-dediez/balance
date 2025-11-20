<?php

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use PHPUnit\Framework\TestCase;

class SqliteImportationDataRepositoryTest extends TestCase
{
    const STORAGE_PATH = __DIR__ . '/../../../../../../../var/sqlite-test/';

    public function testGivenAnImportationWhenICreateAnImportationDataThenDataIsAccessibleThroughAnSqlite(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile($path, 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $file = self::STORAGE_PATH . ((string) $importation->id()) . '.sqlite';

        $this->assertFileExists($file);

        $totalRows = $sut->totalRows($importation->id());

        $this->assertEquals(22, $totalRows);
    }

    public function testGivenAnImportationWithOffsetWhenICreateAnImportationDataThenDataIsAccessibleThroughAnSqlite(): void
    {
        $path = __DIR__ . '/file-to-import-with-offset.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile($path, 3));

        $sut = $this->createSUT();

        $sut->create($importation);

        $file = self::STORAGE_PATH . ((string) $importation->id()) . '.sqlite';

        $this->assertFileExists($file);

        $columns = $sut->getColumns($importation->id());

        $this->assertCount(4, $columns);
        $this->assertEquals('nombre', $columns[0]);
        $this->assertEquals('edad', $columns[1]);
        $this->assertEquals('clase', $columns[2]);
        $this->assertEquals('superpoder', $columns[3]);
    }

    private function createSUT()
    {
        return new SqliteImportationDataRepository(
            storagePath: self::STORAGE_PATH,
        );
    }

    private function cleanStorage()
    {
        if (!file_exists(self::STORAGE_PATH)) {
            mkdir(self::STORAGE_PATH);
        }

        foreach (scandir(self::STORAGE_PATH) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            unlink(self::STORAGE_PATH . $file);
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->cleanStorage();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->cleanStorage();
    }
}
