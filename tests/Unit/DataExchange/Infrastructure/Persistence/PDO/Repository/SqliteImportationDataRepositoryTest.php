<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use PHPUnit\Framework\TestCase;

class SqliteImportationDataRepositoryTest extends TestCase
{
    public const STORAGE_PATH = __DIR__ . '/../../../../../../../var/sqlite-test/';

    public function testGivenANonExistentStoragePathWhenICreateTheRepositoryThenTheDirectoryIsCreated(): void
    {
        $path = __DIR__ . '/../../../../../../../var/sqlite-test-new-dir/';
        if (is_dir($path)) {
            rmdir($path);
        }

        new SqliteImportationDataRepository(storagePath: $path);

        $this->assertDirectoryExists($path);

        rmdir($path);
    }

    public function testGivenAnImportationWhenICreateAnImportationDataThenDataIsAccessibleThroughAnSqlite(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $file = self::STORAGE_PATH . $importation->id() . '.sqlite';

        $this->assertFileExists($file);

        $totalRows = $sut->pendingRows($importation->id());

        $this->assertEquals(22, $totalRows);
    }

    public function testAFileWithManyColumnsIsInsertedInBatchesWithinTheSqliteVariableLimit(): void
    {
        $importation = ImportationMother::fromNameAndFile('wide.csv', new ImportationFile(path: __DIR__ . '/file-to-import-wide.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $this->assertEquals(130, $sut->pendingRows($importation->id()));
        $this->assertCount(40, $sut->getColumns($importation->id()));
        $this->assertSame('r130c40', $sut->getData($importation->id(), 129)['col40'] ?? null);
    }

    public function testGivenAnImportationWithOffsetWhenICreateAnImportationDataThenDataIsAccessibleThroughAnSqlite(): void
    {
        $path = __DIR__ . '/file-to-import-with-offset.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 3));

        $sut = $this->createSUT();

        $sut->create($importation);

        $file = self::STORAGE_PATH . $importation->id() . '.sqlite';

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

        $this->assertEquals(1, $configuration->column);
        $this->assertEquals('edad', $configuration->name);
        $this->assertEquals(null, $configuration->field);
        $this->assertEquals(['14', '15', '16', '17', '18'], $orderedValues);
        $this->assertCount(5, $configuration->translations);
        $this->assertGreaterThan(0, count(array_filter($configuration->translations, fn(ColumnValueTranslation $translation): bool => '14' === $translation->value)));
    }

    public function testGivenAnImportationColumnConfigurationWhenISaveItThenICanRecoverIt(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $nameConfiguration = $sut->getConfiguration($importation->id(), 0);
        $nameConfiguration->field = 'nameOfHeroe';

        $ageConfiguration = $sut->getConfiguration($importation->id(), 1);
        $ageConfiguration->field = 'ageOfHeroe';
        $ageConfiguration->translations = [
            new ColumnValueTranslation(value: '14', translation: 'Infantil'),
            new ColumnValueTranslation(value: '15', translation: 'Junior'),
            new ColumnValueTranslation(value: '16', translation: null),
            new ColumnValueTranslation(value: '17', translation: null),
            new ColumnValueTranslation(value: '18', translation: null),
        ];

        $sut->saveConfigurations($importation->id(), [$nameConfiguration, $ageConfiguration]);

        $configurations = $sut->getAllConfigurations($importation->id());

        $this->assertCount(4, $configurations);
        $this->assertEquals('nameOfHeroe', $configurations[0]->field);
        $this->assertEquals('ageOfHeroe', $configurations[1]->field);
        $this->assertNull($configurations[2]->field);
        $this->assertNull($configurations[3]->field);

        $translations = [];
        foreach ($configurations[1]->translations as $translation) {
            $translations[$translation->value] = $translation->translation;
        }
        ksort($translations);

        $this->assertSame(['14' => 'Infantil', '15' => 'Junior', '16' => null, '17' => null, '18' => null], $translations);
        $this->assertCount(0, array_filter($configurations[0]->translations, fn(ColumnValueTranslation $translation): bool => null !== $translation->translation));

        $this->assertEquals($configurations[1], $sut->getConfiguration($importation->id(), 1));
        $this->assertEquals($configurations[0], $sut->getConfiguration($importation->id(), 0));
    }

    public function testTheConnectionIsReusedAndOpenedWithoutDurability(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $connect = new \ReflectionMethod($sut, 'connect');

        $connection = $connect->invoke($sut, $importation->id());

        $this->assertInstanceOf(\PDO::class, $connection);
        $this->assertSame($connection, $connect->invoke($sut, $importation->id()));
        $journalMode = $connection->query('PRAGMA journal_mode');
        $synchronous = $connection->query('PRAGMA synchronous');

        $this->assertNotFalse($journalMode);
        $this->assertNotFalse($synchronous);
        $this->assertSame('truncate', $journalMode->fetchColumn());
        $this->assertSame(0, (int) $synchronous->fetchColumn());
    }

    public function testRemoveClosesTheConnectionAndDeletesTheFile(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);
        $this->assertSame(22, $sut->pendingRows($importation->id()));

        $connections = new \ReflectionProperty($sut, 'connections');
        $this->assertArrayHasKey((string) $importation->id(), $connections->getValue($sut));

        $sut->remove($importation->id());

        $this->assertArrayNotHasKey((string) $importation->id(), $connections->getValue($sut));

        $file = self::STORAGE_PATH . $importation->id() . '.sqlite';

        $this->assertFileDoesNotExist($file);

        $sut->create($importation);

        $this->assertFileExists($file);
        $this->assertEquals(22, $sut->pendingRows($importation->id()));
        $this->assertCount(4, $sut->getColumns($importation->id()));
    }

    public function testResetForgetsTheCachedConnectionsAndAFollowingCallOpensANewOne(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = new SqliteImportationDataRepository(storagePath: self::STORAGE_PATH);

        $sut->create($importation);
        $this->assertCount(4, $sut->getColumns($importation->id()));

        $connect = new \ReflectionMethod($sut, 'connect');
        $connections = new \ReflectionProperty($sut, 'connections');
        $columns = new \ReflectionProperty($sut, 'columns');

        $connection = $connect->invoke($sut, $importation->id());

        $this->assertNotEmpty($connections->getValue($sut));
        $this->assertNotEmpty($columns->getValue($sut));

        $sut->reset();

        $this->assertSame([], $connections->getValue($sut));
        $this->assertSame([], $columns->getValue($sut));

        $this->assertNotSame($connection, $connect->invoke($sut, $importation->id()));
        $this->assertEquals(22, $sut->pendingRows($importation->id()));
        $this->assertCount(4, $sut->getColumns($importation->id()));
    }

    public function testSavingConfigurationsIsAtomic(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $nameConfiguration = $sut->getConfiguration($importation->id(), 0);
        $nameConfiguration->field = 'nameOfHeroe';

        $ageConfiguration = $sut->getConfiguration($importation->id(), 1);
        $ageConfiguration->field = 'ageOfHeroe';
        $ageConfiguration->translations = [
            new ColumnValueTranslation(value: '14', translation: 'Infantil'),
        ];

        $connect = new \ReflectionMethod($sut, 'connect');
        $connection = $connect->invoke($sut, $importation->id());
        $this->assertInstanceOf(\PDO::class, $connection);

        $connection->exec('DROP TABLE importation_translations');

        try {
            $sut->saveConfigurations($importation->id(), [$nameConfiguration, $ageConfiguration]);
            $this->fail('Saving the configurations must fail when the translations cannot be stored');
        } catch (\PDOException) {
        }

        $columns = $sut->getColumns($importation->id());
        $fields = $connection->query(sprintf('SELECT "%s", "%s" FROM importation_fields LIMIT 1', $columns[0], $columns[1]));

        $this->assertNotFalse($fields);
        $this->assertSame([null, null], $fields->fetch(\PDO::FETCH_NUM));
    }

    public function testGivenAnImportationWhenISaveAnErrorMessageThenICanRecoverIt(): void
    {
        $path = __DIR__ . '/file-to-import.csv';

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: $path, offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $sut->saveResult($importation->id(), 3, 'Error message');

        $result = $sut->getResult($importation->id(), 3);

        $this->assertTrue(is_array($result));
        $this->assertEquals('Error message', $result['error']);

        $otherResult = $sut->getResult($importation->id(), 1);

        $this->assertEmpty($otherResult['error'] ?? '');
    }

    public function testARowCanOnlyBeClaimedOnce(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $this->assertTrue($sut->claimRow($importation->id(), 0));
        $this->assertFalse($sut->claimRow($importation->id(), 0));
        $this->assertTrue($sut->claimRow($importation->id(), 1));
        $this->assertSame(22, $sut->pendingRows($importation->id()));
    }

    public function testAnExecutedRowCannotBeClaimed(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $sut->saveResult($importation->id(), 0, null);

        $this->assertFalse($sut->claimRow($importation->id(), 0));
    }

    public function testTheResultOfARowDoesNotExposeTheClaim(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $this->assertTrue($sut->claimRow($importation->id(), 0));

        $result = $sut->getResult($importation->id(), 0);

        $this->assertIsArray($result);
        $this->assertSame(['row_id', 'nombre', 'edad', 'clase', 'superpoder', 'executed', 'error'], array_keys($result));
        $this->assertNotContains('claimed', $sut->getColumns($importation->id()));
    }

    public function testAnImportationCreatedWithoutTheClaimedColumnCanStillBeClaimed(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv', offset: 0));

        $sut = $this->createSUT();

        $sut->create($importation);

        $connect = new \ReflectionMethod($sut, 'connect');
        $connection = $connect->invoke($sut, $importation->id());
        $this->assertInstanceOf(\PDO::class, $connection);

        $connection->exec('ALTER TABLE importation_data DROP COLUMN claimed');

        $this->assertTrue($sut->claimRow($importation->id(), 0));
        $this->assertFalse($sut->claimRow($importation->id(), 0));
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
            if ('.' === $file) {
                continue;
            }
            if ('..' === $file) {
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
