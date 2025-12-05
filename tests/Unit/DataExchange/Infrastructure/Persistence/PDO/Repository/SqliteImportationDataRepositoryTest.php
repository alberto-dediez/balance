<?php

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
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
        $this->assertCount(5, $configuration->translations);
        $this->assertNotNull(array_filter($configuration->translations, fn (ColumnValueTranslation $translation) => $translation->value == '14'));
    }

    public function testGivenAnImportationColumnConfigurationWhenISaveItThenICanRecoverIt(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $configuration = $sut->getConfiguration($importation->id(), 1);
        $configuration->field = 'ageOfHeroe';
        $configuration->translations = [
            new ColumnValueTranslation(value: '14', translation: 'Infantil'),
            new ColumnValueTranslation(value: '15', translation: 'Junior'),
            new ColumnValueTranslation(value: '16', translation: null),
            new ColumnValueTranslation(value: '17', translation: null),
            new ColumnValueTranslation(value: '18', translation: null),
        ];

        $sut->saveConfiguration($importation->id(), $configuration);

        $recoverConfiguration = $sut->getConfiguration($importation->id(), 1);

        $this->assertEquals('ageOfHeroe', $recoverConfiguration->field);

        /** @var ?ColumnValueTranslation $foundTranslation */
        $foundTranslations = array_filter($recoverConfiguration->translations, fn (ColumnValueTranslation $translation) => $translation->value == '14');
        $this->assertCount(1, $foundTranslations);

        $foundTranslation = array_pop($foundTranslations);
        $this->assertEquals('Infantil', $foundTranslation->translation);
    }

    public function testGivenAnImportationWhenISaveAnErrorMessageThenICanRecoverIt(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $sut->saveError($importation->id(), 3, 'Error message');

        $result = $sut->getResult($importation->id(), 3);

        $this->assertTrue(is_array($result));
        $this->assertEquals('Error message', $result['error']);

        $otherResult = $sut->getResult($importation->id(), 1);

        $this->assertEmpty($otherResult['error']);
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
