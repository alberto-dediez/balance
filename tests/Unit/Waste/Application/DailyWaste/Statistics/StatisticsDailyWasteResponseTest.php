<?php

namespace App\Tests\Unit\Waste\Application\DailyWaste\Statistics;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Application\DailyWaste\Statistics\ByDate;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Application\DailyWaste\Statistics\ByWaste;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Application\DailyWaste\Statistics\ByPurpose;
use App\Waste\Application\DailyWaste\Statistics\ByProperty;
use App\Waste\Application\DailyWaste\Statistics\ByWasteManager;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Waste\Application\DailyWaste\List\ListDailyWastesService;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteResponse;

class StatisticsDailyWasteResponseTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testDates()
    {
        $byDates = [];

        $byDates[] = new ByDate(
            new \DateTimeImmutable("2025-06-01"),
            19.05,
            0.12
        );

        $byDates[] = new ByDate(
            new \DateTimeImmutable("2025-06-02"),
            19.05,
            0.12
        );

        $byDates[] = new ByDate(
            new \DateTimeImmutable("2025-06-05"),
            19.05,
            0.12
        );

        $byDates[] = new ByDate(
            new \DateTimeImmutable("2025-06-04"),
            19.05,
            0.12
        );

        $sut = new StatisticsDailyWasteResponse(
            byDate: $byDates,
            byPurpose: [],
            byWaste: [],
            byWasteManager: [],
            byProperty: []
        );
        
        $result = $sut->dates();

        $this->assertCount(4, $result);

        $this->assertEquals("2025-06-01", $result[0]->format("Y-m-d"));
        $this->assertEquals("2025-06-02", $result[1]->format("Y-m-d"));
        $this->assertEquals("2025-06-04", $result[2]->format("Y-m-d"));
        $this->assertEquals("2025-06-05", $result[3]->format("Y-m-d"));
    }

    public function testPurposes()
    {
        $byPurpose = [];

        $byPurpose[] = new ByPurpose(
            Purpose::Recycling,
            19.05,
            0.12
        );

        $byPurpose[] = new ByPurpose(
            Purpose::Reuse,
            19.05,
            0.12
        );

        $byPurpose[] = new ByPurpose(
            Purpose::Waste,
            19.05,
            0.12
        );

        $sut = new StatisticsDailyWasteResponse(
            byDate: [],
            byPurpose: $byPurpose,
            byWaste: [],
            byWasteManager: [],
            byProperty: []
        );
        
        $result = $sut->purposes();

        $this->assertCount(3, $result);
    }

    public function testWastes()
    {
        $byWaste = [];

        $byWaste[] = new ByWaste(
            WasteMother::fromName('Papel'),
            19.05,
            0.12
        );

        $byWaste[] = new ByWaste(
            WasteMother::fromName('Carton'),
            19.05,
            0.12
        );

        $byWaste[] = new ByWaste(
            WasteMother::fromName('Plastico'),
            19.05,
            0.12
        );

        $sut = new StatisticsDailyWasteResponse(
            byDate: [],
            byPurpose: [],
            byWaste: $byWaste,
            byWasteManager: [],
            byProperty: []
        );
        
        $result = $sut->wastes();

        $this->assertCount(3, $result);

        $this->assertEquals("Carton", $result[0]->name());
        $this->assertEquals("Papel", $result[1]->name());
        $this->assertEquals("Plastico", $result[2]->name());
    }

    public function testWasteManagers()
    {
        $byWasteManager = [];

        $byWasteManager[] = new ByWasteManager(
            WasteManagerMother::fromName('Sadeco'),
            19.05,
            0.12
        );

        $byWasteManager[] = new ByWasteManager(
            WasteManagerMother::fromName('Public'),
            19.05,
            0.12
        );

        $byWasteManager[] = new ByWasteManager(
            WasteManagerMother::fromName('Private'),
            19.05,
            0.12
        );

        $sut = new StatisticsDailyWasteResponse(
            byDate: [],
            byPurpose: [],
            byWaste: [],
            byWasteManager: $byWasteManager,
            byProperty: []
        );
        
        $result = $sut->wasteManagers();

        $this->assertCount(3, $result);

        $this->assertEquals("Private", $result[0]->name());
        $this->assertEquals("Public", $result[1]->name());
        $this->assertEquals("Sadeco", $result[2]->name());
    }

    public function testProperties()
    {
        $byProperty = [];

        $byProperty[] = new ByProperty(
            PropertyMother::fromName('Hotel A'),
            19.05,
            0.12
        );

        $byProperty[] = new ByProperty(
            PropertyMother::fromName('Hotel C'),
            19.05,
            0.12
        );

        $byProperty[] = new ByProperty(
            PropertyMother::fromName('Hotel B'),
            19.05,
            0.12
        );

        $sut = new StatisticsDailyWasteResponse(
            byDate: [],
            byPurpose: [],
            byWaste: [],
            byWasteManager: [],
            byProperty: $byProperty
        );
        
        $result = $sut->properties();

        $this->assertCount(3, $result);

        $this->assertEquals("Hotel A", $result[0]->name());
        $this->assertEquals("Hotel B", $result[1]->name());
        $this->assertEquals("Hotel C", $result[2]->name());
    }

    private function createSUT($repository)
    {
        return new ListDailyWastesService($repository, new DailyWasteAuthorizer());
    }
}