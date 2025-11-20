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
            importationFile: $file,
            accountId: AccountId::generate(),
        );
    }

    public static function from(ImportationId $importationId, string $name, ImportationFile $importationFile, AccountId $accountId): Importation
    {
        return new Importation(
            id: $importationId,
            name: $name,
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
            importationFile: new ImportationFile('/path/to/importation.xlsx', 0),
            accountId: AccountId::generate(),
        );
    }
}
