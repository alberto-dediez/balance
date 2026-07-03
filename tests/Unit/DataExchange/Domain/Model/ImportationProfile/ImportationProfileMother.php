<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Domain\Model\ImportationProfile;

use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfile;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\Security\Domain\Model\Account\AccountId;

class ImportationProfileMother
{
    public static function fromTypeAndAccount(string $type, ?AccountId $accountId): ImportationProfile
    {
        return self::from(
            importationProfileId: ImportationProfileId::generate(),
            type: $type,
            accountId: $accountId,
        );
    }

    /**
     * @param array<string, string>                $fieldsByColumn
     * @param array<string, array<string, string>> $translationsByColumn
     * @param array<string, string>                $defaultValues
     */
    public static function from(
        ImportationProfileId $importationProfileId,
        string $type,
        ?AccountId $accountId,
        array $fieldsByColumn = [],
        array $translationsByColumn = [],
        array $defaultValues = [],
    ): ImportationProfile {
        return new ImportationProfile(
            id: $importationProfileId,
            accountId: $accountId,
            type: $type,
            fieldsByColumn: $fieldsByColumn,
            translationsByColumn: $translationsByColumn,
            defaultValues: $defaultValues,
            updatedAt: new \DateTimeImmutable('NOW'),
        );
    }

    public static function random(): ImportationProfile
    {
        return self::fromTypeAndAccount(
            type: 'measurements',
            accountId: AccountId::generate(),
        );
    }
}
