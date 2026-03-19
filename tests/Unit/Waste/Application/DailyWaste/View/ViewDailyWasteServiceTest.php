<?php

namespace App\Tests\Unit\Waste\Application\DailyWaste\View;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteRequest;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteService;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteResponse;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class ViewDailyWasteServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke(): void
    {
        $dailyWaste = DailyWasteMother::fromDayAndIdAndAccount("2025-10-31", "d1", "account");

        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $dailyWasteRepository->add($dailyWaste);

        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $measurementRepository->add(MeasurementMother::fromDay($dailyWaste));
        $measurementRepository->add(MeasurementMother::fromDay($dailyWaste));
        $measurementRepository->add(MeasurementMother::fromDay($dailyWaste));
        $measurementRepository->add(MeasurementMother::fromDay($dailyWaste));
        $measurementRepository->add(MeasurementMother::fromDay($dailyWaste));

        $sut = $this->createSUT($dailyWasteRepository, $measurementRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('test', Role::Owner, $dailyWaste->accountId());

        $request = new ViewDailyWasteRequest(
            dailyWasteId: $dailyWaste->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ViewDailyWasteResponse);
        $this->assertCount(5, $result->measurements);
        $this->assertNotNull($result->dailyWaste);
        $this->assertTrue($result->dailyWaste->id()->equals($dailyWaste->id()));
    }

    private function createSUT(DailyWasteRepository $dailyWasteRepository, MeasurementRepository $measurementRepository): ViewDailyWasteService
    {
        return new ViewDailyWasteService($dailyWasteRepository, $measurementRepository, new DailyWasteAuthorizer());
    }
}