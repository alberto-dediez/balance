<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\RefrigerantGasRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityMother;
use PHPUnit\Framework\TestCase;

class RefrigerantGasRowViewFactoryTest extends TestCase
{
    private RefrigerantGasRowViewFactory $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->sut = new RefrigerantGasRowViewFactory();
    }

    public function testActivityType(): void
    {
        $this->assertSame(ActivityType::RefrigerantGas, $this->sut->activityType());
    }

    public function testCreatesRowViewFromActivity(): void
    {
        $activity = RefrigerantGasActivityMother::registered();

        $row = $this->sut->create($activity);

        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->refrigerantGasId(), $row->itemId);
        $this->assertSame('kg', $row->unit);
        $this->assertSame(2.0, $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertSame('Sala de máquinas', $row->source);
    }
}
