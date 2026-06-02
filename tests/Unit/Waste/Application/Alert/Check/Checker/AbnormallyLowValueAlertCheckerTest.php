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
use App\Waste\Application\Alert\Check\Checker\AbnormallyLowValueAlertChecker;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class AbnormallyLowValueAlertCheckerTest extends TestCase
{
    public function testEmitsAlertWhenYesterdayValueIsAbnormallyLow(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::fromNameAndAccountAndId('Test', (string) $accountId, (string) $propertyId);
        $propertyRepository->add($property);

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');

        // 28 historical days with load ~100 (mean=100, stddev=0)
        for ($i = 2; $i <= 29; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $today->modify(sprintf('-%d days', $i)),
                load: 100.0,
            ));
        }

        // Yesterday: abnormally low (5 << 100 - 2*0 => any positive stddev will catch this)
        // Use varied historical to get stddev > 0 so threshold makes sense
        // Replace last two days with slightly different values to get a real stddev
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $yesterday,
            load: 5.0,
        ));

        $alertRepository = AlertRepositoryMother::inMemory();
        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            abnormallyLowEnabled: true,
            abnormallyLowMultiplier: 2.0,
        );

        $checker = new AbnormallyLowValueAlertChecker($propertyRepository, $dailyWasteRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config, $today);

        // Since all historical values are 100 and stddev=0, threshold = 100 - 2*0 = 100
        // 5 < 100, so alert should fire
        $this->assertCount(1, $alerts);
        $this->assertSame(5.0, $alerts[0]->value());
    }

    public function testNoAlertWhenYesterdayValueIsNotLow(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::fromNameAndAccountAndId('Test', (string) $accountId, (string) $propertyId);
        $propertyRepository->add($property);

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');

        // Historical with real variance: alternating 90 and 110 → mean=100, stddev=10, threshold=100-2*10=80
        for ($i = 2; $i <= 29; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $today->modify(sprintf('-%d days', $i)),
                load: 0 === $i % 2 ? 90.0 : 110.0,
            ));
        }

        // Yesterday: 85 >= 80 (threshold), so no alert
        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $yesterday,
            load: 85.0,
        ));

        $alertRepository = AlertRepositoryMother::inMemory();
        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            abnormallyLowEnabled: true,
            abnormallyLowMultiplier: 2.0,
        );

        $checker = new AbnormallyLowValueAlertChecker($propertyRepository, $dailyWasteRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config, $today);

        $this->assertCount(0, $alerts);
    }

    public function testNoAlertWhenYesterdayValueIsZero(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $wasteManagerId = WasteManagerId::generate();

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $property = PropertyMother::fromNameAndAccountAndId('Test', (string) $accountId, (string) $propertyId);
        $propertyRepository->add($property);

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');

        for ($i = 2; $i <= 29; ++$i) {
            $dailyWasteRepository->add(DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $today->modify(sprintf('-%d days', $i)),
                load: 100.0,
            ));
        }

        $dailyWasteRepository->add(DailyWasteMother::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            accountId: $accountId,
            day: $yesterday,
            load: 0.0,
        ));

        $alertRepository = AlertRepositoryMother::inMemory();
        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            abnormallyLowEnabled: true,
        );

        $checker = new AbnormallyLowValueAlertChecker($propertyRepository, $dailyWasteRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config, $today);

        $this->assertCount(0, $alerts);
    }
}
