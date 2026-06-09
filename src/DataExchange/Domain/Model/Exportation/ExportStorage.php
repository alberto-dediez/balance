<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Security\Domain\Model\Account\AccountId;

interface ExportStorage
{
    /**
     * Persists the contents located at $localPath and returns an opaque reference.
     */
    public function store(ExportationId $id, ?AccountId $accountId, string $extension, string $localPath): StoredFile;

    /**
     * @return resource a readable stream of the stored file contents
     */
    public function read(StoredFile $file): mixed;

    public function delete(StoredFile $file): void;

    public function exists(StoredFile $file): bool;
}
