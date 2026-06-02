<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Alert;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertType;

class AlertMother
{
    public static function detectedAlert(): Alert
    {
        return self::from(
            type: AlertType::NoMeasurements,
            propertyId: 'property-id-test',
            accountId: 'account-id-test',
        );
    }

    public static function from(
        string $id = '',
        AlertType $type = AlertType::NoMeasurements,
        string $propertyId = 'property-id-test',
        ?string $wasteId = null,
        string $accountId = 'account-id-test',
        ?float $value = null,
        ?float $threshold = null,
    ): Alert {
        $alertId = '' !== $id ? new AlertId($id) : AlertId::generate();
        $alert = new Alert(id: $alertId);
        $now = new \DateTimeImmutable();
        $alert->detect(
            type: $type,
            propertyId: new PropertyId($propertyId),
            wasteId: null,
            accountId: new AccountId($accountId),
            detectedAt: $now,
            from: $now->modify('-7 days'),
            to: $now,
            value: $value,
            threshold: $threshold,
        );

        return $alert;
    }

    public static function fromIdAndAccount(string $id, string $accountId): Alert
    {
        return self::from(id: $id, accountId: $accountId);
    }

    public static function random(): Alert
    {
        $types = AlertType::cases();

        return self::from(
            type: $types[array_rand($types)],
            accountId: 'account-' . rand(100, 999),
        );
    }
}
