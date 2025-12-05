<?php

namespace App\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Infrastructure\Persistence\PDO\Tools\FileExtractor;
use Symfony\Component\Filesystem\Exception\IOException;

class SqliteImportationDataRepository implements ImportationDataRepository
{
    public function __construct(private string $storagePath)
    {
        if (!is_dir($this->storagePath) && mkdir($this->storagePath, 0755, true)) {
            throw new IOException(
                message: "Failed to create storage directory '{$this->storagePath}'",
                code: 503,
                path: $this->storagePath
            );
        }
    }

    public function create(Importation $importation): void
    {
        if (null === $importation->file()) {
            throw new \RuntimeException("You must provide a file to import");
        }

        $extractor = new FileExtractor($importation->file());

        try {
            $this->createData($importation, $extractor);
            $this->createFields($importation, $extractor);
            $this->createTranslations($importation, $extractor);
        } catch (\PDOException $e) {
            throw new IOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function createData(Importation $importation, FileExtractor $extractor): void
    {
        $createTableSql = sprintf(
            'CREATE TABLE importation_data (row_id INTEGER PRIMARY KEY AUTOINCREMENT, %s, `error` TEXT DEFAULT NULL)',
            implode(', ', array_map(fn($col) => "\"{$col}\" TEXT", $extractor->getColumns()))
        );

        $insertSql = sprintf(
            'INSERT INTO importation_data (%s) VALUES ',
            implode(', ', array_map(fn($col) => "\"{$col}\"", $extractor->getColumns()))
        );

        $connection = $this->connect($importation->id());
        $connection->exec($createTableSql);

        $query = $insertSql;

        $toInsert = 0;
        $toInsertValues = [];

        while (null !== ($row = $extractor->getRow())) {
            $query .= sprintf(
                "(%s),",
                implode(', ', array_map(fn($col) => ":{$col}_{$toInsert}", $extractor->getColumns()))
            );

            foreach ($extractor->getColumns() as $col) {
                $value = $row[$col];

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                $toInsertValues[":{$col}_{$toInsert}"] = $value;
            }

            $toInsert++;
        }

        if ($toInsert === 0) {
            return ;
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
            implode(', ', array_map(fn($col) => "\"{$col}\" VARCHAR(100)", $extractor->getColumns()))
        );

        $insertForFieldsSql = sprintf(
            'INSERT INTO importation_fields (%s) VALUES (%s)',
            implode(', ', array_map(fn($col) => "\"{$col}\"", $extractor->getColumns())),
            implode(', ', array_map(fn($col) => "NULL", $extractor->getColumns()))
        );

        $connection = $this->connect($importation->id());

        $connection->exec($createTableForFieldsSql);
        $connection->exec($insertForFieldsSql);
    }

    private function createTranslations(Importation $importation, FileExtractor $extractor): void
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

    public function getColumns(ImportationId $importationId): array
    {
        $connection = $this->connect($importationId);

        return $connection->query("SELECT name FROM pragma_table_info('importation_data') WHERE name != 'row_id' AND name != 'error';")->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getValues(ImportationId $importationId, int $column): array
    {
        $connection = $this->connect($importationId);

        $columns = $this->getColumns($importationId);
        $columnName = $columns[$column] ?? '';

        $sql = sprintf("SELECT DISTINCT(`%s`) FROM importation_data", $columnName);

        return $connection->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getAllConfigurations(ImportationId $importationId): array
    {
        $columns = $this->getColumns($importationId);

        if (count($columns) === 0) {
            return [];
        }

        $result = [];

        foreach ($columns as $column => $columnName) {
            $result[] = $this->getConfiguration($importationId, $column);
        }

        return $result;
    }

    public function getConfiguration(ImportationId $importationId, int $column): ImportationColumnConfiguration
    {
        $columns = $this->getColumns($importationId);
        $columnName = $columns[$column] ?? '';

        $values = $this->getValues($importationId, $column);

        $connection = $this->connect($importationId);
        $field = $connection->query(sprintf("SELECT `%s` FROM importation_fields LIMIT 1", $columnName))->fetch(\PDO::FETCH_COLUMN);

        $translations = $this->getTranslations($importationId, $columnName, $values);

        return new ImportationColumnConfiguration(
            column: $column,
            name: $columnName,
            field: $field,
            values: $values,
            translations: $translations
        );
    }

    /**
     * @return ColumnValueTranslation[]
     */
    private function getTranslations(ImportationId $importationId, string $columnName, array $values): array
    {
        $translations = array_map(function ($value) {
            return new ColumnValueTranslation(value: $value, translation: null);
        }, array_filter($values, fn($value) => $value !== null));

        $connection = $this->connect($importationId);

        $statement = $connection->prepare("SELECT column_value, column_translation FROM importation_translations WHERE column_name = ?");
        $statement->bindValue(1, $columnName);
        $statement->execute();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $found = array_filter($translations, fn (ColumnValueTranslation $translation) => $translation->value == $row['column_value']);

            $translation = count($found) > 0 ? array_pop($found) : new ColumnValueTranslation(value: $row['column_value'], translation: null);
            $translation->translation = $row['column_translation'];
        }

        return $translations;
    }

    public function saveConfiguration(ImportationId $importationId, ImportationColumnConfiguration $configuration): void
    {
        $connection = $this->connect($importationId);
        $statement = $connection->prepare(sprintf("UPDATE importation_fields SET %s = ?", $configuration->name));
        $statement->bindValue(1, $configuration->field);
        $statement->execute();

        if (count($configuration->translations) === 0) {
            return ;
        }

        $sql = "INSERT OR REPLACE INTO importation_translations
        (column_name, column_value, column_translation)
        VALUES (:name, :val, :trans)";

        $statement = $connection->prepare($sql);

        foreach ($configuration->translations as $translation) {
            $statement->execute([
                ':name'  => $configuration->name,
                ':val'   => $translation->value,
                ':trans' => $translation->translation,
            ]);
        }
    }

    public function getData(ImportationId $importationId, int $rowNumber): ?array
    {
        $connection = $this->connect($importationId);

        $sql = "SELECT * FROM importation_data WHERE row_id = ?";

        $statement = $connection->prepare($sql);
        $statement->bindValue(1, $rowNumber+1);
        $statement->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (is_array($row)) {
            unset($row['row_id']);
            unset($row['error']);

            return $row;
        }

        return null;
    }

    public function saveError(ImportationId $importationId, int $rowNumber, ?string $error): void
    {
        $connection = $this->connect($importationId);

        $sql = "UPDATE importation_data SET `error` = ? WHERE row_id = ?";

        $statement = $connection->prepare($sql);
        $statement->bindValue(2, $error);
        $statement->bindValue(1, $rowNumber+1);
        $statement->execute();
    }

    public function getResult(ImportationId $importationId, int $rowNumber): ?array
    {
        $connection = $this->connect($importationId);

        $sql = "SELECT * FROM importation_data WHERE row_id = ?";

        $statement = $connection->prepare($sql);
        $statement->bindValue(1, $rowNumber+1);
        $statement->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (is_array($row)) {
            return $row;
        }

        return null;
    }

    public function totalRows(ImportationId $importationId): int
    {
        $connection = $this->connect($importationId);

        return $connection->query("SELECT COUNT(*) FROM importation_data")->fetchColumn();
    }

    public function remove(ImportationId $importationId): void
    {
        $dbPath = $this->getDbPath($importationId);

        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }

    private function getDbPath(ImportationId $importationId): string
    {
        return sprintf("%s%s.sqlite", $this->storagePath, (string) $importationId);
    }

    private function connect(ImportationId $importationId): \PDO
    {
        $dbPath = $this->getDbPath($importationId);

        try {
            $pdo = new \PDO('sqlite:' . $dbPath);

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Solo optimizar en la escritura, no en la lectura
            if (defined('PDO::SQLITE_ATTR_OPEN_FLAGS')) {
                // Flags para asegurar WAL mode si es posible
            } else {
                // Configuración óptima para inserciones masivas
                $pdo->exec('PRAGMA synchronous = OFF');
                $pdo->exec('PRAGMA journal_mode = MEMORY');
            }

            return $pdo;
        } catch (\PDOException $e) {
            throw new IOException(message: "Error creating connection: " . $e->getMessage(), code: 503);
        }
    }
}
