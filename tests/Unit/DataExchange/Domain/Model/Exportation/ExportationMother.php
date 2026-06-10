<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Domain\Model\Exportation;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportationStatus;
use App\DataExchange\Domain\Model\Exportation\StoredFile;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class ExportationMother
{
    /** @param string[] $columns */
    public static function registered(
        string $type = 'users',
        ?AccountId $accountId = null,
        ?UserId $registeredBy = null,
        ExportationFormat $format = ExportationFormat::Xlsx,
        array $columns = ['username'],
        ?\DateTimeImmutable $validUntil = null,
    ): Exportation {
        return new Exportation(
            id: ExportationId::generate(),
            type: $type,
            status: ExportationStatus::Started,
            format: $format,
            columns: $columns,
            filterCriteria: [],
            accountId: $accountId ?? AccountId::generate(),
            registeredAt: new \DateTimeImmutable('NOW'),
            validUntil: $validUntil ?? new \DateTimeImmutable('+7 days'),
            registeredBy: $registeredBy,
        );
    }

    public static function expiredWithFile(): Exportation
    {
        return new Exportation(
            id: ExportationId::generate(),
            type: 'users',
            status: ExportationStatus::Completed,
            format: ExportationFormat::Xlsx,
            columns: ['username'],
            filterCriteria: [],
            accountId: AccountId::generate(),
            file: new StoredFile('shared/expired.xlsx', 'xlsx'),
            registeredAt: new \DateTimeImmutable('-10 days'),
            validUntil: new \DateTimeImmutable('-3 days'),
        );
    }

    public static function validWithFile(): Exportation
    {
        return new Exportation(
            id: ExportationId::generate(),
            type: 'users',
            status: ExportationStatus::Completed,
            format: ExportationFormat::Xlsx,
            columns: ['username'],
            filterCriteria: [],
            accountId: AccountId::generate(),
            file: new StoredFile('shared/valid.xlsx', 'xlsx'),
            registeredAt: new \DateTimeImmutable('NOW'),
            validUntil: new \DateTimeImmutable('+7 days'),
        );
    }
}
