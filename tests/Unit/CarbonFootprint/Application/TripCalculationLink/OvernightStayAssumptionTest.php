<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink;

use App\CarbonFootprint\Application\TripCalculationLink\OvernightStayAssumption;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyType;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class OvernightStayAssumptionTest extends TestCase
{
    public function testAppliesToAnAccommodationWithBothScope(): void
    {
        $property = $this->property(PropertyType::Hotel);
        $link = TripCalculationLinkMother::fromPropertyIdAndScope((string) $property->id(), TripCalculationScope::Both);

        $this->assertTrue(OvernightStayAssumption::appliesTo($link, $property));
    }

    public function testDoesNotApplyToAPropertyOfOtherType(): void
    {
        $property = $this->property(PropertyType::Other);
        $link = TripCalculationLinkMother::fromPropertyIdAndScope((string) $property->id(), TripCalculationScope::Both);

        $this->assertFalse(OvernightStayAssumption::appliesTo($link, $property));
    }

    public function testDoesNotApplyToAPropertyWithoutType(): void
    {
        $property = $this->property(null);
        $link = TripCalculationLinkMother::fromPropertyIdAndScope((string) $property->id(), TripCalculationScope::Both);

        $this->assertFalse(OvernightStayAssumption::appliesTo($link, $property));
    }

    public function testDoesNotApplyWithOvernightStayScope(): void
    {
        $property = $this->property(PropertyType::Hotel);
        $link = TripCalculationLinkMother::fromPropertyIdAndScope((string) $property->id(), TripCalculationScope::OvernightStay);

        $this->assertFalse(OvernightStayAssumption::appliesTo($link, $property));
    }

    public function testDoesNotApplyWithTransportScope(): void
    {
        $property = $this->property(PropertyType::Hotel);
        $link = TripCalculationLinkMother::fromPropertyIdAndScope((string) $property->id(), TripCalculationScope::Transport);

        $this->assertFalse(OvernightStayAssumption::appliesTo($link, $property));
    }

    public function testDoesNotApplyToAnAccountLink(): void
    {
        $link = TripCalculationLinkMother::forAccount('account-id');

        $this->assertFalse(OvernightStayAssumption::appliesTo($link, null));
    }

    private function property(?PropertyType $propertyType): Property
    {
        return PropertyMother::fromNameAndAccountAndIdAndType('Property', 'account-id', (string) PropertyId::generate(), $propertyType);
    }
}
