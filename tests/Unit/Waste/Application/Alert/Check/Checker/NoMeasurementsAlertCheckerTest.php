<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\NoMeasurementsAlertChecker;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class NoMeasurementsAlertCheckerTest extends TestCase
{
    public function testTypeReturnsNoMeasurements(): void
    {
        $sut = $this->createSUT();

        $this->assertEquals(AlertType::NoMeasurements, $sut->type());
    }

    public function testReturnsNoAlertsWhenPropertyHasMeasurementsWithinPeriod(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $recentDay = $checkDate->modify('-3 days');

        $dailyWaste = DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            accountId: $accountId,
            day: $recentDay,
        );
        $dailyWasteRepository->add($dailyWaste);

        $sut = new NoMeasurementsAlertChecker($propertyRepository, $dailyWasteRepository);

        $rule = $this->createRule();
        $alerts = $sut->check($accountId, [$rule], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testDetectsAlertWhenNoMeasurementsForPropertyInPeriod(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $sut = new NoMeasurementsAlertChecker($propertyRepository, $dailyWasteRepository);

        $rule = $this->createRule();
        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$rule], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(AlertType::NoMeasurements, $alerts[0]->type());
        $this->assertEquals('property-id-test', (string) $alerts[0]->propertyId());
        $this->assertEquals('account-id-test', (string) $alerts[0]->accountId());
    }

    public function testSkipsAlertIfAlreadyInExistingAlertsIndex(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $propertyId = new PropertyId('property-id-test');

        $property = PropertyMother::fromNameAndAccountAndId('Test Property', 'account-id-test', 'property-id-test');
        $propertyRepository->add($property);

        $sut = new NoMeasurementsAlertChecker($propertyRepository, $dailyWasteRepository);

        $rule = $this->createRule();
        $checkDate = new \DateTimeImmutable('2026-06-01');

        $existingAlerts = new ExistingAlertsIndex();
        $existingAlerts->add(AlertType::NoMeasurements, $propertyId, null);

        $alerts = $sut->check($accountId, [$rule], $checkDate, $existingAlerts);

        $this->assertCount(0, $alerts);
    }

    public function testChecksOnlySpecificPropertyWhenRuleHasPropertyId(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $targetProperty = PropertyMother::fromNameAndAccountAndId('Target', 'account-id-test', 'target-property');
        $otherProperty = PropertyMother::fromNameAndAccountAndId('Other', 'account-id-test', 'other-property');
        $propertyRepository->add($targetProperty);
        $propertyRepository->add($otherProperty);

        $sut = new NoMeasurementsAlertChecker($propertyRepository, $dailyWasteRepository);

        $rule = new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::NoMeasurements,
            enabled: true,
            parameters: ['noMeasurementsDays' => 7],
            propertyId: new PropertyId('target-property'),
        );

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$rule], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals('target-property', (string) $alerts[0]->propertyId());
    }

    private function createSUT(): NoMeasurementsAlertChecker
    {
        return new NoMeasurementsAlertChecker(
            propertyRepository: PropertyRepositoryMother::inMemory(),
            dailyWasteRepository: DailyWasteRepositoryMother::inMemory(),
        );
    }

    private function createRule(): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::NoMeasurements,
            enabled: true,
            parameters: ['noMeasurementsDays' => 7],
        );
    }
}
