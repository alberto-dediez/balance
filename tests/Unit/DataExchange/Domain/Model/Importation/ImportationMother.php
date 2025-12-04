<?php

namespace App\Tests\Unit\DataExchange\Domain\Model\Importation;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Security\Domain\Model\Account\AccountId;

class ImportationMother
{
    public static function fromNameAndFile(string $name, ImportationFile $file): Importation
    {
        return self::from(
            importationId: ImportationId::generate(),
            name: $name,
            type: 'measurements',
            importationFile: $file,
            accountId: AccountId::generate(),
        );
    }

    public static function fromIdAndAccountAndFile(string $id, string $accountId, ImportationFile $file): Importation
    {
        $parts = explode('/', $file->path());

        return self::from(
            importationId: new ImportationId($id),
            name: $parts[count($parts) - 1],
            type: 'measurements',
            importationFile: $file,
            accountId: new AccountId($accountId),
        );
    }

    public static function from(ImportationId $importationId, string $name, string $type, ImportationFile $importationFile, AccountId $accountId): Importation
    {
        return new Importation(
            id: $importationId,
            name: $name,
            type: $type,
            accountId: $accountId,
            file: $importationFile,
            registeredAt: new \DateTimeImmutable('NOW'),
        );
    }

    public static function random(): Importation
    {
        return self::from(
            importationId: ImportationId::generate(),
            name: 'importation-' . rand(100, 999),
            type: 'measurements',
            importationFile: new ImportationFile(__DIR__ . '/file-to-import.csv', 0),
            accountId: AccountId::generate(),
        );
    }
}
