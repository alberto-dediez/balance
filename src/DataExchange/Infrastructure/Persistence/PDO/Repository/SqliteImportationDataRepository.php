<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Infrastructure\Persistence\PDO\Tools\FileExtractor;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Contracts\Service\ResetInterface;

class SqliteImportationDataRepository implements ImportationDataRepository, ResetInterface
{
    public const BATCH_SIZE = 100;

    private const int MAX_SQL_VARIABLES = 999;

    /** @var array<string, \PDO> */
    private array $connections = [];

    /** @var array<string, list<string>> */
    private array $columns = [];

    public function __construct(private readonly string $storagePath)
    {
        if (!is_dir($this->storagePath) && !mkdir($this->storagePath, 0755, true)) {
            throw new IOException(message: "Failed to create storage directory '{$this->storagePath}'", code: 503, path: $this->storagePath);
        }
    }

    public function create(Importation $importation): void
    {
        if (!$importation->file() instanceof ImportationFile) {
            throw new \RuntimeException('You must provide a file to import');
        }

        $extractor = new FileExtractor($importation->file());

        $this->forget($importation->id());

        try {
            $this->createData($importation, $extractor);
            $this->createFields($importation, $extractor);
            $this->createTranslations($importation);
        } catch (\PDOException $e) {
            throw new IOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function createData(Importation $importation, FileExtractor $extractor): void
    {
        $createTableSql = sprintf(
            'CREATE TABLE importation_data (row_id INTEGER PRIMARY KEY AUTOINCREMENT, %s, `claimed` BOOLEAN DEFAULT 0, `executed` BOOLEAN DEFAULT 0, `error` TEXT DEFAULT NULL)',
            implode(', ', array_map(fn($col): string => "\"{$col}\" TEXT", $extractor->getColumns())),
        );

        $insertSql = sprintf(
            'INSERT INTO importation_data (%s) VALUES ',
            implode(', ', array_map(fn($col): string => "\"{$col}\"", $extractor->getColumns())),
        );

        $connection = $this->connect($importation->id());
        $connection->exec($createTableSql);

        $batchSize = max(1, min(self::BATCH_SIZE, intdiv(self::MAX_SQL_VARIABLES, max(1, count($extractor->getColumns())))));
        $query = $insertSql;

        $toInsert = 0;
        $toInsertValues = [];

        while (null !== ($row = $extractor->getRow())) {
            $query .= sprintf(
                '(%s),',
                implode(', ', array_map(fn($col): string => ":{$col}_{$toInsert}", $extractor->getColumns())),
            );

            foreach ($extractor->getColumns() as $col) {
                $value = $row[$col];

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                $toInsertValues[":{$col}_{$toInsert}"] = $value;
            }

            ++$toInsert;

            if ($toInsert >= $batchSize) {
                $query = substr($query, 0, -1);
                $statement = $connection->prepare($query);

                foreach ($toInsertValues as $attr => $value) {
                    $statement->bindValue($attr, $value);
                }

                $statement->execute();

                $toInsertValues = [];
                $toInsert = 0;
                $query = $insertSql;
            }
        }

        if (0 === $toInsert) {
            return;
        }

        $query = substr($query, 0, -1);
        $statement = $connection->prepare($query);

        foreach ($toInsertValues as $attr => $value) {
            $statement->bindValue($attr, $value);
        }

        $statement->execute();
    }

    private function createFields(Importation $importation, FileExtractor $extractor): void
    {
        $createTableForFieldsSql = sprintf(
            'CREATE TABLE importation_fields (row_id INTEGER PRIMARY KEY AUTOINCREMENT, %s)',
            implode(', ', array_map(fn($col): string => "\"{$col}\" VARCHAR(100)", $extractor->getColumns())),
        );

        $insertForFieldsSql = sprintf(
            'INSERT INTO importation_fields (%s) VALUES (%s)',
            implode(', ', array_map(fn($col): string => "\"{$col}\"", $extractor->getColumns())),
            implode(', ', array_map(fn($col): string => 'NULL', $extractor->getColumns())),
        );

        $connection = $this->connect($importation->id());

        $connection->exec($createTableForFieldsSql);
        $connection->exec($insertForFieldsSql);
    }

    private function createTranslations(Importation $importation): void
    {
        $createTableSql = 'CREATE TABLE importation_translations (
            column_name TEXT NOT NULL,
            column_value TEXT NOT NULL,
            column_translation TEXT DEFAULT NULL,
            PRIMARY KEY (column_name, column_value)
        );';

        $connection = $this->connect($importation->id());

        $connection->exec($createTableSql);
    }

    /** @return array<int, string> */
    public function getColumns(ImportationId $importationId): array
    {
        $key = (string) $importationId;

        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }

        $connection = $this->connect($importationId);

        $stmt = $connection->query("SELECT name FROM pragma_table_info('importation_data') WHERE name != 'row_id' AND name != 'error' AND name != 'executed' AND name != 'claimed';");

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        /** @var list<string> $columns */
        $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->columns[$key] = $columns;

        return $columns;
    }

    /** @return array<int, string> */
    public function getValues(ImportationId $importationId, int $column): array
    {
        $columns = $this->getColumns($importationId);

        return $this->distinctValues($this->connect($importationId), $columns[$column] ?? '');
    }

    /** @return ImportationColumnConfiguration[] */
    public function getAllConfigurations(ImportationId $importationId): array
    {
        $columns = $this->getColumns($importationId);

        if (0 === count($columns)) {
            return [];
        }

        $connection = $this->connect($importationId);

        $stmt = $connection->query('SELECT * FROM importation_fields LIMIT 1');

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        $fields = $stmt->fetch(\PDO::FETCH_ASSOC);
        $fields = is_array($fields) ? $fields : [];

        $stmt = $connection->query('SELECT column_name, column_value, column_translation FROM importation_translations');

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        $translationsByColumn = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $translationsByColumn[$row['column_name']][$row['column_value']] = $row['column_translation'];
        }

        $result = [];

        foreach ($columns as $column => $columnName) {
            $result[] = $this->buildConfiguration(
                column: $column,
                name: $columnName,
                field: $fields[$columnName] ?? null,
                values: $this->distinctValues($connection, $columnName),
                translationsByValue: $translationsByColumn[$columnName] ?? [],
            );
        }

        return $result;
    }

    public function getConfiguration(ImportationId $importationId, int $column): ImportationColumnConfiguration
    {
        $columns = $this->getColumns($importationId);
        $columnName = $columns[$column] ?? '';

        $connection = $this->connect($importationId);

        $stmt = $connection->query(sprintf('SELECT "%s" FROM importation_fields LIMIT 1', $columnName));

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        $field = $stmt->fetch(\PDO::FETCH_COLUMN);

        $statement = $connection->prepare('SELECT column_value, column_translation FROM importation_translations WHERE column_name = ?');
        $statement->bindValue(1, $columnName);
        $statement->execute();

        $translationsByValue = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $translationsByValue[$row['column_value']] = $row['column_translation'];
        }

        return $this->buildConfiguration(
            column: $column,
            name: $columnName,
            field: is_string($field) ? $field : null,
            values: $this->distinctValues($connection, $columnName),
            translationsByValue: $translationsByValue,
        );
    }

    /**
     * @param array<int, string|null>    $values
     * @param array<string, string|null> $translationsByValue
     */
    private function buildConfiguration(int $column, string $name, ?string $field, array $values, array $translationsByValue): ImportationColumnConfiguration
    {
        $translations = [];

        foreach ($values as $key => $value) {
            if (null === $value) {
                continue;
            }

            $translations[$key] = new ColumnValueTranslation(value: $value, translation: $translationsByValue[$value] ?? null);
        }

        return new ImportationColumnConfiguration(
            column: $column,
            name: $name,
            field: $field,
            values: $values,
            translations: $translations,
        );
    }

    /** @return array<int, string> */
    private function distinctValues(\PDO $connection, string $columnName): array
    {
        $stmt = $connection->query(sprintf('SELECT DISTINCT("%s") FROM importation_data', $columnName));

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** @param ImportationColumnConfiguration[] $configurations */
    public function saveConfigurations(ImportationId $importationId, array $configurations): void
    {
        if ([] === $configurations) {
            return;
        }

        $connection = $this->connect($importationId);
        $connection->beginTransaction();

        try {
            $assignments = implode(', ', array_map(fn(ImportationColumnConfiguration $configuration): string => sprintf('"%s" = ?', $configuration->name), $configurations));
            $statement = $connection->prepare(sprintf('UPDATE importation_fields SET %s', $assignments));
            $statement->execute(array_values(array_map(fn(ImportationColumnConfiguration $configuration): ?string => $configuration->field, $configurations)));

            $translationStatement = $connection->prepare('INSERT OR REPLACE INTO importation_translations (column_name, column_value, column_translation) VALUES (:name, :val, :trans)');

            foreach ($configurations as $configuration) {
                foreach ($configuration->translations as $translation) {
                    $translationStatement->execute([
                        ':name'  => $configuration->name,
                        ':val'   => $translation->value,
                        ':trans' => $translation->translation,
                    ]);
                }
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public function getData(ImportationId $importationId, int $rowNumber): ?array
    {
        $columns = $this->getColumns($importationId);
        $connection = $this->connect($importationId);

        $sql = sprintf(
            'SELECT %s FROM importation_data WHERE row_id = ?',
            implode(', ', array_map(fn($col): string => "\"{$col}\"", $columns)),
        );

        $statement = $connection->prepare($sql);
        $statement->bindValue(1, $rowNumber + 1);
        $statement->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    public function saveResult(ImportationId $importationId, int $rowNumber, ?string $error): void
    {
        $connection = $this->connect($importationId);

        $sql = 'UPDATE importation_data SET `error` = ?, `executed` = 1 WHERE row_id = ?';

        $statement = $connection->prepare($sql);
        $statement->bindValue(1, $error);
        $statement->bindValue(2, $rowNumber + 1);
        $statement->execute();
    }

    public function claimRow(ImportationId $importationId, int $rowNumber): bool
    {
        $connection = $this->connect($importationId);
        $this->ensureClaimedColumn($connection);

        $statement = $connection->prepare('UPDATE importation_data SET `claimed` = 1 WHERE row_id = ? AND `claimed` = 0 AND `executed` = 0');
        $statement->bindValue(1, $rowNumber + 1);
        $statement->execute();

        return 1 === $statement->rowCount();
    }

    private function ensureClaimedColumn(\PDO $connection): void
    {
        if ($this->hasClaimedColumn($connection)) {
            return;
        }

        try {
            $connection->exec('ALTER TABLE importation_data ADD COLUMN "claimed" BOOLEAN DEFAULT 0');
        } catch (\PDOException $exception) {
            if (!$this->hasClaimedColumn($connection)) {
                throw $exception;
            }
        }
    }

    /** @phpstan-impure */
    private function hasClaimedColumn(\PDO $connection): bool
    {
        $stmt = $connection->query("SELECT COUNT(*) FROM pragma_table_info('importation_data') WHERE name = 'claimed'");

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        return 0 < (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function getResult(ImportationId $importationId, int $rowNumber): ?array
    {
        $columns = $this->getColumns($importationId);
        $connection = $this->connect($importationId);

        $sql = sprintf(
            'SELECT row_id, %s, "executed", "error" FROM importation_data WHERE row_id = ?',
            implode(', ', array_map(fn($col): string => "\"{$col}\"", $columns)),
        );

        $statement = $connection->prepare($sql);
        $statement->bindValue(1, $rowNumber + 1);
        $statement->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (is_array($row)) {
            return $row;
        }

        return null;
    }

    public function pendingRows(ImportationId $importationId): int
    {
        $connection = $this->connect($importationId);

        $stmt = $connection->query('SELECT COUNT(*) FROM importation_data WHERE `executed` != 1');

        if (false === $stmt) {
            throw new \RuntimeException('Failed to execute query.');
        }

        return (int) $stmt->fetchColumn();
    }

    public function remove(ImportationId $importationId): void
    {
        $this->forget($importationId);

        $dbPath = $this->getDbPath($importationId);

        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }

    public function reset(): void
    {
        $this->connections = [];
        $this->columns = [];
    }

    private function getDbPath(ImportationId $importationId): string
    {
        return sprintf('%s%s.sqlite', $this->storagePath, (string) $importationId);
    }

    private function forget(ImportationId $importationId): void
    {
        $key = (string) $importationId;

        unset($this->connections[$key], $this->columns[$key]);
    }

    private function connect(ImportationId $importationId): \PDO
    {
        $key = (string) $importationId;

        if (isset($this->connections[$key])) {
            return $this->connections[$key];
        }

        $dbPath = $this->getDbPath($importationId);

        try {
            $pdo = new \PDO('sqlite:' . $dbPath);

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA journal_mode = TRUNCATE');
            $pdo->exec('PRAGMA synchronous = OFF');
            $pdo->exec('PRAGMA temp_store = MEMORY');

            $this->connections[$key] = $pdo;

            return $pdo;
        } catch (\PDOException $e) {
            throw new IOException(message: 'Error creating connection: ' . $e->getMessage(), code: 503, previous: $e);
        }
    }
}
