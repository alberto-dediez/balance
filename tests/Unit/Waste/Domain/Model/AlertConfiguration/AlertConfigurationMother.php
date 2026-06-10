<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertConfiguration;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Application\Alert\Check\Checker\AbnormallyHighValueAlertChecker;
use App\Waste\Application\Alert\Check\Checker\AbnormallyLowValueAlertChecker;
use App\Waste\Application\Alert\Check\Checker\FixedThresholdAlertChecker;
use App\Waste\Application\Alert\Check\Checker\InactiveUserAlertChecker;
use App\Waste\Application\Alert\Check\Checker\NoMeasurementsAlertChecker;
use App\Waste\Application\Alert\Check\Checker\ThresholdExceededAlertChecker;
use App\Waste\Application\Alert\Check\Checker\UnusedContainerAlertChecker;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;

class AlertConfigurationMother
{
    public static function registeredAlertConfiguration(): AlertConfiguration
    {
        return self::from(accountId: 'account-id-test');
    }

    public static function from(
        string $id = '',
        string $accountId = 'account-id-test',
        bool $noMeasurementsEnabled = true,
        int $noMeasurementsDays = NoMeasurementsAlertChecker::DEFAULT_NO_MEASUREMENTS_DAYS,
        bool $abnormallyHighEnabled = false,
        float $deviationMultiplier = AbnormallyHighValueAlertChecker::DEFAULT_DEVIATION_MULTIPLIER,
        bool $thresholdExceededEnabled = false,
        float $thresholdPercentage = ThresholdExceededAlertChecker::DEFAULT_THRESHOLD_PERCENTAGE,
        bool $fixedThresholdEnabled = false,
        float $thresholdKg = FixedThresholdAlertChecker::DEFAULT_THRESHOLD_KG,
        bool $abnormallyLowEnabled = false,
        float $abnormallyLowMultiplier = AbnormallyLowValueAlertChecker::DEFAULT_DEVIATION_MULTIPLIER,
        bool $unusedContainerEnabled = false,
        int $unusedDays = UnusedContainerAlertChecker::DEFAULT_UNUSED_DAYS,
        bool $inactiveUserEnabled = false,
        int $inactiveDays = InactiveUserAlertChecker::DEFAULT_INACTIVE_DAYS,
    ): AlertConfiguration {
        $configId = '' !== $id ? new AlertConfigurationId($id) : AlertConfigurationId::generate();
        $config = new AlertConfiguration(id: $configId);
        $config->register(
            accountId: new AccountId($accountId),
            items: [
                self::item(AlertType::NoMeasurements, $noMeasurementsEnabled, ['noMeasurementsDays' => $noMeasurementsDays]),
                self::item(AlertType::AbnormallyHigh, $abnormallyHighEnabled, ['deviationMultiplier' => $deviationMultiplier]),
                self::item(AlertType::ThresholdExceeded, $thresholdExceededEnabled, ['thresholdPercentage' => $thresholdPercentage]),
                self::item(AlertType::FixedThreshold, $fixedThresholdEnabled, ['thresholdKg' => $thresholdKg]),
                self::item(AlertType::AbnormallyLow, $abnormallyLowEnabled, ['deviationMultiplier' => $abnormallyLowMultiplier]),
                self::item(AlertType::UnusedContainer, $unusedContainerEnabled, ['unusedDays' => $unusedDays]),
                self::item(AlertType::InactiveUser, $inactiveUserEnabled, ['inactiveDays' => $inactiveDays]),
            ],
        );

        return $config;
    }

    /** @param array<string, mixed> $parameters */
    private static function item(AlertType $type, bool $enabled, array $parameters): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: $type,
            enabled: $enabled,
            parameters: $parameters,
        );
    }

    public static function fromIdAndAccount(string $id, string $accountId): AlertConfiguration
    {
        return self::from(id: $id, accountId: $accountId);
    }

    public static function random(): AlertConfiguration
    {
        return self::from(
            accountId: 'account-' . rand(100, 999),
        );
    }
}
