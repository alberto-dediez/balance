<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\DailyWaste\Statistics;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteRequest;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteResponse;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteService;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use PHPUnit\Framework\TestCase;

class StatisticsDailyWasteServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $wasteManagerRepository = WasteManagerRepositoryMother::inMemory();

        $account = AccountMother::random();

        $propertyA = PropertyMother::fromNameAndAccountAndId('Hotel A', (string) $account->id(), 'PA');
        $propertyRepository->add($propertyA);

        $propertyB = PropertyMother::fromNameAndAccountAndId('Hotel B', (string) $account->id(), 'PB');
        $propertyRepository->add($propertyB);

        $propertyC = PropertyMother::fromNameAndAccountAndId('Hotel C', (string) $account->id(), 'PC');
        $propertyRepository->add($propertyC);

        $wasteA = WasteMother::random();
        $wasteRepository->add($wasteA);

        $wasteB = WasteMother::random();
        $wasteRepository->add($wasteB);

        $wasteManagerA = WasteManagerMother::fromAccount($account->id());
        $wasteManagerRepository->add($wasteManagerA);

        $wasteManagerB = WasteManagerMother::fromAccount($account->id());
        $wasteManagerRepository->add($wasteManagerB);

        $dailyWasteRepository->add(
            DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyB->id(),
                wasteId: $wasteB->id(),
                wasteManagerId: $wasteManagerA->id(),
                purpose: Purpose::Recycling,
                accountId: $account->id(),
                day: new \DateTimeImmutable('2025-06-01'),
                load: 100,
            ),
        );

        $dailyWasteRepository->add(
            DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyA->id(),
                wasteId: $wasteA->id(),
                wasteManagerId: $wasteManagerA->id(),
                purpose: Purpose::Waste,
                accountId: $account->id(),
                day: new \DateTimeImmutable('2025-06-02'),
                load: 25.5,
            ),
        );

        $dailyWasteRepository->add(
            DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyA->id(),
                wasteId: $wasteA->id(),
                wasteManagerId: $wasteManagerA->id(),
                purpose: Purpose::Reuse,
                accountId: $account->id(),
                day: new \DateTimeImmutable('2025-06-02'),
                load: 25.5,
            ),
        );

        $dailyWasteRepository->add(
            DailyWasteMother::from(
                dailyWasteId: DailyWasteId::generate(),
                propertyId: $propertyC->id(),
                wasteId: $wasteB->id(),
                wasteManagerId: $wasteManagerA->id(),
                purpose: Purpose::Recycling,
                accountId: $account->id(),
                day: new \DateTimeImmutable('2025-06-03'),
                load: 100,
            ),
        );

        $sut = $this->createSUT(
            dailyWasteRepository: $dailyWasteRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            wasteManagerRepository: $wasteManagerRepository,
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('test', Role::Owner, $account->id());

        $request = new StatisticsDailyWasteRequest(
            accountId: $account->id(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof StatisticsDailyWasteResponse);

        $this->assertCount(3, $result->byDate);
        $this->assertEquals('2025-06-01', $result->byDate[0]->date->format('Y-m-d'));
        $this->assertEquals('2025-06-02', $result->byDate[1]->date->format('Y-m-d'));
        $this->assertEquals(51, $result->byDate[1]->load);
        $this->assertEqualsWithDelta(0.2032, $result->byDate[1]->percentage, 0.0001);
        $this->assertEquals('2025-06-03', $result->byDate[2]->date->format('Y-m-d'));

        $this->assertCount(3, $result->byProperty);
        $this->assertEquals('Hotel A', $result->byProperty[0]->property->name());
        $this->assertEquals(51, $result->byProperty[0]->load);
        $this->assertEqualsWithDelta(0.2032, $result->byProperty[0]->percentage, 0.0001);
        $this->assertEquals('Hotel B', $result->byProperty[1]->property->name());
        $this->assertEquals('Hotel C', $result->byProperty[2]->property->name());

        $this->assertCount(3, $result->byPurpose);
        $this->assertCount(2, $result->byWaste);
        $this->assertCount(1, $result->byWasteManager);
    }

    private function createSUT(
        DailyWasteRepository $dailyWasteRepository,
        PropertyRepository $propertyRepository,
        WasteRepository $wasteRepository,
        WasteManagerRepository $wasteManagerRepository,
    ): StatisticsDailyWasteService {
        return new StatisticsDailyWasteService(
            dailyWasteRepository: $dailyWasteRepository,
            wasteManagerRepository: $wasteManagerRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            authorizer: new DailyWasteAuthorizer(),
        );
    }
}
