<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\FixedThresholdAlertChecker;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class FixedThresholdAlertCheckerTest extends TestCase
{
    public function testEmitsAlertWhenYesterdayTotalExceedsFixedThreshold(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::fromNameAndAccountAndId('Test', (string) $accountId, (string) $propertyId);
        $propertyRepository->add($property);

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $yesterday = new \DateTimeImmutable('yesterday');

        $dw = DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $yesterday,
            load: 600.0,
        );
        $dailyWasteRepository->add($dw);

        $alertRepository = AlertRepositoryMother::inMemory();
        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            fixedThresholdEnabled: true,
            thresholdKg: 500.0,
        );

        $checker = new FixedThresholdAlertChecker($propertyRepository, $dailyWasteRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(1, $alerts);
        $this->assertSame(600.0, $alerts[0]->value());
        $this->assertSame(500.0, $alerts[0]->threshold());
    }

    public function testNoAlertWhenYesterdayTotalBelowThreshold(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::fromNameAndAccountAndId('Test', (string) $accountId, (string) $propertyId);
        $propertyRepository->add($property);

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $yesterday = new \DateTimeImmutable('yesterday');

        $dw = DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $yesterday,
            load: 300.0,
        );
        $dailyWasteRepository->add($dw);

        $alertRepository = AlertRepositoryMother::inMemory();
        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            fixedThresholdEnabled: true,
            thresholdKg: 500.0,
        );

        $checker = new FixedThresholdAlertChecker($propertyRepository, $dailyWasteRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(0, $alerts);
    }

    public function testNoDuplicateAlertWhenOneAlreadyExists(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::fromNameAndAccountAndId('Test', (string) $accountId, (string) $propertyId);
        $propertyRepository->add($property);

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $yesterday = new \DateTimeImmutable('yesterday');

        $dw = DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $yesterday,
            load: 600.0,
        );
        $dailyWasteRepository->add($dw);

        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            fixedThresholdEnabled: true,
            thresholdKg: 500.0,
        );

        $checker = new FixedThresholdAlertChecker($propertyRepository, $dailyWasteRepository, $alertRepository);

        $firstRun = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));
        foreach ($firstRun as $alert) {
            $alertRepository->add($alert);
        }

        $secondRun = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(1, $firstRun);
        $this->assertCount(0, $secondRun);
    }
}
