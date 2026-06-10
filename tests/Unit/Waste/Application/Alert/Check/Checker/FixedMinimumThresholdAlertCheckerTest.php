<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\FixedMinimumThresholdAlertChecker;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class FixedMinimumThresholdAlertCheckerTest extends TestCase
{
    public function testTypeReturnsFixedMinimumThreshold(): void
    {
        $this->assertEquals(AlertType::FixedMinimumThreshold, $this->createSUT()->type());
    }

    public function testReturnsNoAlertsWhenTotalLoadIsAboveThreshold(): void
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

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 100.0,
        ));

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        $alerts = $sut->check($accountId, [$this->createRule(50.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testDetectsAlertWhenTotalLoadIsBelowThreshold(): void
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

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 10.0,
        ));

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        $alerts = $sut->check($accountId, [$this->createRule(50.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(AlertType::FixedMinimumThreshold, $alerts[0]->type());
        $this->assertEquals('property-id-test', (string) $alerts[0]->propertyId());
        $this->assertEquals('account-id-test', (string) $alerts[0]->accountId());
        $this->assertEquals(10.0, $alerts[0]->value());
        $this->assertEquals(50.0, $alerts[0]->threshold());
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

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 10.0,
        ));

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        $existingAlerts = new ExistingAlertsIndex();
        $existingAlerts->add(AlertType::FixedMinimumThreshold, $propertyId, $wasteId, $checkDate);

        $alerts = $sut->check($accountId, [$this->createRule(50.0)], $checkDate, $existingAlerts);

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenNoDataForCheckDate(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        $alerts = $sut->check($accountId, [$this->createRule(50.0)], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenNoPropertiesForAccount(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$this->createRule(50.0)], $checkDate, new ExistingAlertsIndex());

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

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $targetPropertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 10.0,
        ));

        $rule = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::FixedMinimumThreshold,
            enabled: true,
            parameters: ['thresholdKg' => 50.0],
            propertyId: $targetPropertyId,
        );

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        $alerts = $sut->check($accountId, [$rule], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals('target-property', (string) $alerts[0]->propertyId());
    }

    public function testUsesCustomThresholdKgFromRuleParameters(): void
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

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 30.0,
        ));

        $sut = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository,
        );

        // With threshold=50 → 30 is below it → alert
        $ruleWithHighThreshold = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::FixedMinimumThreshold,
            enabled: true,
            parameters: ['thresholdKg' => 50.0],
        );

        $alerts = $sut->check($accountId, [$ruleWithHighThreshold], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(50.0, $alerts[0]->threshold());

        // With threshold=20 → 30 is NOT below it → no alert
        $dailyWasteRepository2 = DailyWasteRepositoryMother::inMemory();
        $dailyWasteRepository2->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 30.0,
        ));

        $sut2 = new FixedMinimumThresholdAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteRepository: $dailyWasteRepository2,
        );

        $ruleWithLowThreshold = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::FixedMinimumThreshold,
            enabled: true,
            parameters: ['thresholdKg' => 20.0],
        );

        $alerts2 = $sut2->check($accountId, [$ruleWithLowThreshold], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts2);
    }

    private function createSUT(): FixedMinimumThresholdAlertChecker
    {
        return new FixedMinimumThresholdAlertChecker(
            propertyRepository: PropertyRepositoryMother::inMemory(),
            dailyWasteRepository: DailyWasteRepositoryMother::inMemory(),
        );
    }

    private function createRule(float $thresholdKg = FixedMinimumThresholdAlertChecker::DEFAULT_THRESHOLD_KG): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::FixedMinimumThreshold,
            enabled: true,
            parameters: ['thresholdKg' => $thresholdKg],
        );
    }
}
