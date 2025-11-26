<?php

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
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

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

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

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 3));

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

    public function testGivenAnImportationWhenICreateAnImportationDataThenIGetConfigurationOfEveryColumn(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $configuration = $sut->getConfiguration($importation->id(), 1);

        $orderedValues = $configuration->values;
        sort($orderedValues);

        $this->assertNotNull($configuration);
        $this->assertEquals(1, $configuration->column);
        $this->assertEquals('edad', $configuration->name);
        $this->assertEquals(null, $configuration->field);
        $this->assertEquals(['14','15','16','17','18'], $orderedValues);
        $this->assertEquals(['14' => null,'15' => null,'16' => null,'17' => null,'18' => null], $configuration->translations);
    }

    public function testGivenAnImportationColumnConfigurationWhenISaveItThenICanRecoverIt(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $configuration = $sut->getConfiguration($importation->id(), 1);
        $configuration->field = 'ageOfHeroe';
        $configuration->translations = ['14' => 'Infantil','15' => 'Junior','16' => null,'17' => null,'18' => null];

        $sut->saveConfiguration($importation->id(), $configuration);

        $recoverConfiguration = $sut->getConfiguration($importation->id(), 1);

        $this->assertEquals('ageOfHeroe', $recoverConfiguration->field);
        $this->assertEquals('Infantil', $recoverConfiguration->translations['14']);
    }

    private function createSUT(): ImportationDataRepository
    {
        return new SqliteImportationDataRepository(
            storagePath: self::STORAGE_PATH,
        );
    }

    private function cleanStorage(): void
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
