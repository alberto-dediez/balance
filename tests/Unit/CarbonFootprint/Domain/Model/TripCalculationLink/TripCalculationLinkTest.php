<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRegistered;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkScopeUpdated;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class TripCalculationLinkTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterStoresPropertyAccountAndToken(): void
    {
        $propertyId = PropertyId::generate();
        $accountId = AccountId::generate();

        $tripCalculationLink = new TripCalculationLink(TripCalculationLinkId::generate());
        $tripCalculationLink->register(
            propertyId: $propertyId,
            accountId: $accountId,
            token: TripCalculationLink::generateToken(),
        );

        $storedPropertyId = $tripCalculationLink->propertyId();
        $storedAccountId = $tripCalculationLink->accountId();

        $this->assertNotNull($storedPropertyId);
        $this->assertNotNull($storedAccountId);
        $this->assertTrue($propertyId->equals($storedPropertyId));
        $this->assertTrue($accountId->equals($storedAccountId));
        $this->assertNotEmpty($tripCalculationLink->token());

        $events = array_filter(
            $tripCalculationLink->recordedEvents(),
            static fn($event) => $event instanceof TripCalculationLinkRegistered,
        );
        $this->assertNotEmpty($events);
    }

    public function testCollectsTransportAndOvernightStayByDefault(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $this->assertSame(TripCalculationScope::Both, $tripCalculationLink->scope());
        $this->assertTrue($tripCalculationLink->scope()->asksForBoth());
        $this->assertTrue($tripCalculationLink->scope()->includesTransport());
        $this->assertTrue($tripCalculationLink->scope()->includesOvernightStay());
    }

    public function testUpdateScopeStoresTheNewScope(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $tripCalculationLink->updateScope(TripCalculationScope::OvernightStay);

        $this->assertSame(TripCalculationScope::OvernightStay, $tripCalculationLink->scope());
        $this->assertFalse($tripCalculationLink->scope()->includesTransport());
        $this->assertFalse($tripCalculationLink->scope()->asksForBoth());

        $events = array_filter(
            $tripCalculationLink->recordedEvents(),
            static fn($event) => $event instanceof TripCalculationLinkScopeUpdated,
        );
        $this->assertNotEmpty($events);
    }

    public function testGeneratesADifferentTokenEveryTime(): void
    {
        $this->assertNotSame(TripCalculationLink::generateToken(), TripCalculationLink::generateToken());
    }
}
