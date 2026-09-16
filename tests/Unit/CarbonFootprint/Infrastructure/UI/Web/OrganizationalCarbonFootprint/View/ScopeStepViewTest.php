<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowViewFactoryRegistry;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CombustibleRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ElectricityRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\RefrigerantGasRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\RoadTransportRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use PHPUnit\Framework\TestCase;

class ScopeStepViewTest extends TestCase
{
    private ScopeStepView $sut;

    protected function setUp(): void
    {
        $this->sut = new ScopeStepView(new ActivityRowViewFactoryRegistry([
            new CombustibleRowViewFactory(),
            new RoadTransportRowViewFactory(),
            new RefrigerantGasRowViewFactory(),
            new ElectricityRowViewFactory(),
        ]));
    }

    public function testScope1BuildsTheThreeCatalogsAndRows(): void
    {
        $activity = CombustibleActivityMother::registered(new AccountId('account-id'));

        $combustible = CombustibleMother::fromName('Gas natural');
        $roadTransport = RoadTransportMother::fromName('Camión');
        $refrigerantGas = RefrigerantGasMother::fromName('R-410A');

        $view = $this->sut->build([
            new StageSection(Scope::Scope1->sections()[0], [$activity], [$combustible]),
            new StageSection(Scope::Scope1->sections()[1], [], [$roadTransport]),
            new StageSection(Scope::Scope1->sections()[2], [], [$refrigerantGas]),
        ]);

        $this->assertSame([$combustible], $view['combustibles']);
        $this->assertSame([$roadTransport], $view['roadTransports']);
        $this->assertSame([$refrigerantGas], $view['refrigerantGases']);
        $this->assertCount(1, $view['combustibleRows']);
        $this->assertCount(0, $view['roadTransportRows']);
        $this->assertSame(Unit::cases(), $view['units']);
    }

    public function testMissingSectionsBecomeEmptyLists(): void
    {
        $marketer = ElectricityMarketerMother::fromName('Marketer');
        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $view = $this->sut->build([
            new StageSection(Scope::Scope2->sections()[0], [], [$marketer], [$gdOFactor]),
        ]);

        $this->assertSame([$marketer], $view['electricityMarketers']);
        $this->assertSame([$gdOFactor], $view['gdOFactors']);
        $this->assertSame([], $view['combustibles']);
        $this->assertSame([], $view['roadTransports']);
        $this->assertSame([], $view['refrigerantGases']);
        $this->assertSame([], $view['combustibleRows']);
        $this->assertSame([], $view['refrigerantGasRows']);
        $this->assertSame([], $view['electricityRows']);
    }
}
