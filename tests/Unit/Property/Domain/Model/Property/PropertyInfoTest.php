<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyInfoTest extends TestCase
{
    public function testTheDefaultTypeIsAnAccommodation(): void
    {
        $this->assertTrue((new PropertyInfo())->isAccommodation());
    }

    public function testOtherTypeIsNotAnAccommodation(): void
    {
        $this->assertFalse((new PropertyInfo(propertyType: PropertyType::Other))->isAccommodation());
    }

    public function testWithoutTypeIsNotAnAccommodation(): void
    {
        $this->assertFalse((new PropertyInfo(propertyType: null))->isAccommodation());
    }
}
