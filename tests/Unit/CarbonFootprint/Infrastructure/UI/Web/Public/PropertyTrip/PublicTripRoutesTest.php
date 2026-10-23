<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PublicTripRoutes;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use PHPUnit\Framework\TestCase;

class PublicTripRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testPropertyLinksUseThePropertyTripRoutes(): void
    {
        $routes = PublicTripRoutes::for(TripCalculationLinkMother::registeredTripCalculationLink());

        $this->assertSame('public_carbon_footprint_property_trip', $routes->prefix);
        $this->assertSame('public_carbon_footprint_property_trip', $routes->name());
        $this->assertSame('public_carbon_footprint_property_trip_result', $routes->name('_result'));
    }

    public function testAccountLinksUseTheTripRoutes(): void
    {
        $routes = PublicTripRoutes::for(TripCalculationLinkMother::forAccount('account-id'));

        $this->assertSame('public_carbon_footprint_trip', $routes->prefix);
        $this->assertSame('public_carbon_footprint_trip_result', $routes->name('_result'));
    }
}
