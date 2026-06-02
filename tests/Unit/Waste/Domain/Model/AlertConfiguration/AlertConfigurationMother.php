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
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;

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
                'NoMeasurements' => ['enabled' => $noMeasurementsEnabled, 'parameters' => ['noMeasurementsDays' => $noMeasurementsDays]],
                'AbnormallyHigh' => ['enabled' => $abnormallyHighEnabled, 'parameters' => ['deviationMultiplier' => $deviationMultiplier]],
                'ThresholdExceeded' => ['enabled' => $thresholdExceededEnabled, 'parameters' => ['thresholdPercentage' => $thresholdPercentage]],
                'FixedThreshold' => ['enabled' => $fixedThresholdEnabled, 'parameters' => ['thresholdKg' => $thresholdKg]],
                'AbnormallyLow' => ['enabled' => $abnormallyLowEnabled, 'parameters' => ['deviationMultiplier' => $abnormallyLowMultiplier]],
                'UnusedContainer' => ['enabled' => $unusedContainerEnabled, 'parameters' => ['unusedDays' => $unusedDays]],
                'InactiveUser' => ['enabled' => $inactiveUserEnabled, 'parameters' => ['inactiveDays' => $inactiveDays]],
            ],
        );

        return $config;
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
