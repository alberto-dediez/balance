<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class ActivityTypeTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testFromActivityResolvesRefrigerantGas(): void
    {
        $this->assertSame(ActivityType::RefrigerantGas, ActivityType::fromActivity(RefrigerantGasActivityMother::registered()));
    }

    public function testFromActivityResolvesPurchaseService(): void
    {
        $this->assertSame(ActivityType::PurchaseService, ActivityType::fromActivity(PurchaseServiceActivityMother::registered()));
    }

    public function testFromActivityResolvesCombustible(): void
    {
        $this->assertSame(ActivityType::Combustible, ActivityType::fromActivity(CombustibleActivityMother::registered()));
    }
}
