<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\ThresholdExceededAlertChecker;
use App\Waste\Application\Alert\Check\DailyWasteWindowProvider;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class ThresholdExceededAlertCheckerTest extends TestCase
{
    public function testTypeReturnsThresholdExceeded(): void
    {
        $this->assertEquals(AlertType::ThresholdExceeded, $this->createSUT()->type());
    }

    public function testReturnsNoAlertsWhenValueIsWithinThreshold(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        // Historical mean = 100 → threshold at 150% = 150
        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }

        // Today's value at 140 is below the 150% threshold (150)
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 140.0,
        ));

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule(150.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testDetectsAlertWhenValueExceedsPercentageThreshold(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        // Historical mean = 100 → threshold at 150% = 150
        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }

        // Today's value at 200 exceeds the 150% threshold (150)
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 200.0,
        ));

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule(150.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(AlertType::ThresholdExceeded, $alerts[0]->type());
        $this->assertEquals('property-id-test', (string) $alerts[0]->propertyId());
        $this->assertEquals('account-id-test', (string) $alerts[0]->accountId());
        $this->assertEquals(200.0, $alerts[0]->value());
        $this->assertEquals(150.0, $alerts[0]->threshold());
    }

    public function testSkipsAlertIfAlreadyInExistingAlertsIndex(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 200.0,
        ));

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $existingAlerts = new ExistingAlertsIndex();
        $existingAlerts->add(AlertType::ThresholdExceeded, $propertyId, $wasteId, $checkDate);

        $alerts = $sut->check($accountId, [$this->createRule(150.0)], $checkDate, $existingAlerts);

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenNoDailyWasteForCheckDate(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule(150.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenNoHistoricalData(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        // Only today's entry, no historical data
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 1000.0,
        ));

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule(150.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testChecksOnlySpecificPropertyWhenRuleHasPropertyId(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $targetPropertyId = new PropertyId('target-property');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $targetProperty = PropertyMother::fromNameAndAccountAndId('Target', 'account-id-test', 'target-property');
        $propertyRepository->add($targetProperty);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $targetPropertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $targetPropertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 200.0,
        ));

        $rule = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::ThresholdExceeded,
            enabled: true,
            parameters: ['thresholdPercentage' => 150.0],
            propertyId: $targetPropertyId,
        );

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$rule], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals('target-property', (string) $alerts[0]->propertyId());
    }

    public function testUsesCustomThresholdPercentageFromRuleParameters(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        // Historical mean = 100
        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }

        // Today's value: 120
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 120.0,
        ));

        $sut = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        // With 110% → threshold = 100 * 1.10 = 110 → 120 exceeds it → alert
        $ruleLow = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::ThresholdExceeded,
            enabled: true,
            parameters: ['thresholdPercentage' => 110.0],
        );

        $alerts = $sut->check($accountId, [$ruleLow], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);

        // With 200% → threshold = 100 * 2.00 = 200 → 120 does NOT exceed it → no alert
        $dailyWasteRepository2 = DailyWasteRepositoryMother::inMemory();
        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository2->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 100.0,
            ));
        }
        $dailyWasteRepository2->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 120.0,
        ));

        $sut2 = new ThresholdExceededAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository2),
        );

        $ruleHigh = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::ThresholdExceeded,
            enabled: true,
            parameters: ['thresholdPercentage' => 200.0],
        );

        $alerts2 = $sut2->check($accountId, [$ruleHigh], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts2);
    }

    private function createSUT(): ThresholdExceededAlertChecker
    {
        return new ThresholdExceededAlertChecker(
            propertyRepository: PropertyRepositoryMother::inMemory(),
            dailyWasteWindowProvider: new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory()),
        );
    }

    private function createRule(float $thresholdPercentage = ThresholdExceededAlertChecker::DEFAULT_THRESHOLD_PERCENTAGE): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::ThresholdExceeded,
            enabled: true,
            parameters: ['thresholdPercentage' => $thresholdPercentage],
        );
    }
}
