<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Geo;

use App\Shared\Infrastructure\Geo\IntlCountryRegionCode;
use PHPUnit\Framework\TestCase;

class IntlCountryRegionCodeTest extends TestCase
{
    public function testConvertsAnAlpha3CodeToAlpha2(): void
    {
        $this->assertSame('ES', (new IntlCountryRegionCode())->alpha2('ESP'));
    }

    public function testKeepsAnAlpha2Code(): void
    {
        $this->assertSame('FR', (new IntlCountryRegionCode())->alpha2(' fr '));
    }

    public function testReturnsNullForAnUnknownCode(): void
    {
        $sut = new IntlCountryRegionCode();

        $this->assertNull($sut->alpha2('XYZ'));
        $this->assertNull($sut->alpha2('ZZ'));
        $this->assertNull($sut->alpha2('ESPA'));
    }
}
