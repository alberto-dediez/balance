<?php

namespace App\DataExchange\Infrastructure\Persistence\PDO\Repository;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Infrastructure\Persistence\PDO\Tools\FileExtractor;
use Symfony\Component\Filesystem\Exception\IOException;

class SqliteImportationDataRepository implements ImportationDataRepository
{
    private const TABLE_NAME = 'importation_data';

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

        $createTableSql = sprintf(
            'CREATE TABLE %s (%s)',
            self::TABLE_NAME,
            implode(', ', array_map(fn($col) => "\"{$col}\"", $extractor->getColumns()))
        );

        $insertSql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::TABLE_NAME,
            implode(', ', array_map(fn($col) => "\"{$col}\"", $extractor->getColumns())),
            implode(', ', array_fill(0, count($extractor->getColumns()), '?'))
        );

        try {
            $connection = $this->connect($importation->id());
            $connection->exec($createTableSql);

            $connection->beginTransaction();

            $statement = $connection->prepare($insertSql);

            while (null !== ($row = $extractor->getRow())) {
                $statement->execute(array_values($row));
            }

            $connection->commit();
        } catch (\PDOException $e) {
            $connection->rollBack();

            throw new IOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getColumns(ImportationId $importationId): array
    {
        $connection = $this->connect($importationId);

        $sql = sprintf("SELECT name FROM pragma_table_info('%s');", self::TABLE_NAME);

        return $connection->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getValues(ImportationId $importationId, int $column): array
    {
        $connection = $this->connect($importationId);

        $columns = $this->getColumns($importationId);
        $columnName = $columns[$column] ?? '';

        $sql = sprintf("SELECT DISTINCT(%s) FROM %s", $columnName, self::TABLE_NAME);

        return $connection->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getData(ImportationId $importationId, int $rowNumber): array
    {
        $connection = $this->connect($importationId);

        $sql = sprintf("SELECT * FROM %s LIMIT 1 OFFSET %s", self::TABLE_NAME, $rowNumber);

        $statement = $connection->prepare($sql);
        $statement->execute();

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function totalRows(ImportationId $importationId): int
    {
        $connection = $this->connect($importationId);

        $sql = sprintf("SELECT COUNT(*) FROM %s", self::TABLE_NAME);

        return $connection->query($sql)->fetchColumn();
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
