<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\AbnormallyHighValueAlertChecker;
use App\Waste\Application\Alert\Check\DailyWasteWindowProvider;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class AbnormallyHighValueAlertCheckerTest extends TestCase
{
    public function testTypeReturnsAbnormallyHigh(): void
    {
        $this->assertEquals(AlertType::AbnormallyHigh, $this->createSUT()->type());
    }

    public function testReturnsNoAlertsWhenValueIsWithinNormalRange(): void
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

        // Historical values: mean=100, stddev≈0 → threshold ≈ 100 + 2*0 = 100
        // Add 10 historical days with load=100 so stddev is small
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

        // Today's value is normal (100), well within threshold
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 100.0,
        ));

        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testDetectsAlertWhenValueIsAbnormallyHigh(): void
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

        // Historical values around 100 (mean=100, stddev≈0) → threshold ≈ 100
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

        // Today's value is abnormally high (1000 >> 100 + 2*0)
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 1000.0,
        ));

        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(AlertType::AbnormallyHigh, $alerts[0]->type());
        $this->assertEquals('property-id-test', (string) $alerts[0]->propertyId());
        $this->assertEquals('account-id-test', (string) $alerts[0]->accountId());
        $this->assertEquals(1000.0, $alerts[0]->value());
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
            load: 1000.0,
        ));

        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $existingAlerts = new ExistingAlertsIndex();
        $existingAlerts->add(AlertType::AbnormallyHigh, $propertyId, $wasteId, $checkDate);

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, $existingAlerts);

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

        // No entry for checkDate itself
        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenInsufficientHistoricalData(): void
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

        // Only one historical point (need at least 2)
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate->modify('-1 days'),
            load: 100.0,
        ));

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 1000.0,
        ));

        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

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
            load: 1000.0,
        ));

        $rule = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::AbnormallyHigh,
            enabled: true,
            parameters: ['deviationMultiplier' => 2.0],
            propertyId: $targetPropertyId,
        );

        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$rule], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals('target-property', (string) $alerts[0]->propertyId());
    }

    public function testUsesCustomDeviationMultiplierFromRuleParameters(): void
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

        // Historical: alternating 90/110, mean=100, stddev=10
        // With multiplier=1.0: threshold = 100 + 1*10 = 110 → 115 triggers
        // With multiplier=10.0: threshold = 100 + 10*10 = 200 → 115 does NOT trigger
        for ($i = 1; $i <= 10; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $checkDate->modify("-{$i} days"),
                load: 0 === $i % 2 ? 90.0 : 110.0,
            ));
        }

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $checkDate,
            load: 115.0,
        ));

        $ruleWithHighMultiplier = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::AbnormallyHigh,
            enabled: true,
            parameters: ['deviationMultiplier' => 10.0],
        );

        $sut = new AbnormallyHighValueAlertChecker(
            propertyRepository: $propertyRepository,
            dailyWasteWindowProvider: new DailyWasteWindowProvider($dailyWasteRepository),
        );

        $alerts = $sut->check($accountId, [$ruleWithHighMultiplier], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    private function createSUT(): AbnormallyHighValueAlertChecker
    {
        return new AbnormallyHighValueAlertChecker(
            propertyRepository: PropertyRepositoryMother::inMemory(),
            dailyWasteWindowProvider: new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory()),
        );
    }

    private function createRule(): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::AbnormallyHigh,
            enabled: true,
            parameters: ['deviationMultiplier' => AbnormallyHighValueAlertChecker::DEFAULT_DEVIATION_MULTIPLIER],
        );
    }
}
