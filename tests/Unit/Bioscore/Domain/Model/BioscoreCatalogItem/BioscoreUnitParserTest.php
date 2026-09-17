<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnitParser;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

class BioscoreUnitParserTest extends TestCase
{
    /** @return iterable<string, array{string, ?Unit}> */
    public static function units(): iterable
    {
        yield 'kg' => ['kg', Unit::Kilogram];
        yield 'kgs' => ['kgs', Unit::Kilogram];
        yield 'kilogramo' => ['kilogramo', Unit::Kilogram];
        yield 'kilogramos' => ['Kilogramos', Unit::Kilogram];
        yield 'g' => ['g', Unit::Gram];
        yield 'gramo' => ['gramo', Unit::Gram];
        yield 'gramos' => ['gramos', Unit::Gram];
        yield 't' => ['t', Unit::Tonne];
        yield 'tn' => ['tn', Unit::Tonne];
        yield 'tonelada' => ['tonelada', Unit::Tonne];
        yield 'toneladas' => ['toneladas', Unit::Tonne];
        yield 'l' => ['l', Unit::Liter];
        yield 'lt' => ['lt', Unit::Liter];
        yield 'lts' => ['lts', Unit::Liter];
        yield 'litro' => ['litro', Unit::Liter];
        yield 'litros' => ['litros', Unit::Liter];
        yield 'm3' => ['m3', Unit::CubicMeter];
        yield 'm cubed' => ['m³', Unit::CubicMeter];
        yield 'metro cubico' => ['metro cubico', Unit::CubicMeter];
        yield 'metros cubicos' => ['metros cubicos', Unit::CubicMeter];
        yield 'kwh' => ['kWh', Unit::KWh];
        yield 'kwhpcs' => ['kWhPCS', Unit::KWhPCS];
        yield 'kwh pcs' => ['kWh PCS', Unit::KWhPCS];
        yield 'trailing dot' => ['kg.', Unit::Kilogram];
        yield 'empty' => ['', null];
        yield 'unknown' => ['kgCO2/l', null];
    }

    /** @dataProvider units */
    public function testParse(string $rawUnit, ?Unit $expected): void
    {
        $this->assertSame($expected, BioscoreUnitParser::parse($rawUnit));
    }

    public function testEmptyUnitIsAlwaysCompatible(): void
    {
        $this->assertTrue(BioscoreUnitParser::isCompatible('', Unit::Liter));
    }

    public function testDifferentMagnitudeIsNotCompatible(): void
    {
        $this->assertFalse(BioscoreUnitParser::isCompatible('kg', Unit::Liter));
    }

    public function testSameMagnitudeIsCompatible(): void
    {
        $this->assertTrue(BioscoreUnitParser::isCompatible('lt', Unit::CubicMeter));
    }

    public function testUnknownUnitIsNotCompatible(): void
    {
        $this->assertFalse(BioscoreUnitParser::isCompatible('kgCO2/l', Unit::Liter));
    }
}
