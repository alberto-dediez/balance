<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\InvalidHexColorException;
use PHPUnit\Framework\TestCase;

class HexColorTest extends TestCase
{
    public function testItNormalisesToUppercase(): void
    {
        $this->assertSame('#8A2432', (new HexColor('#8a2432'))->value);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function invalidValues(): array
    {
        return [
            [''],
            ['8A2432'],
            ['#8A243'],
            ['#8A24322'],
            ['#GGGGGG'],
            ['#8A2432;color:red'],
            ['red'],
        ];
    }

    /**
     * @dataProvider invalidValues
     */
    public function testItRejectsInvalidValues(string $value): void
    {
        $this->expectException(InvalidHexColorException::class);

        new HexColor($value);
    }

    public function testShadeMatchesBootstrapShadeColor(): void
    {
        $this->assertSame('#35637A', (new HexColor('#3E7490'))->shade(0.15)->value);
        $this->assertSame('#A6B300', (new HexColor('#C3D300'))->shade(0.15)->value);
    }

    public function testTintMatchesBootstrapTintColor(): void
    {
        $this->assertSame('#D8E3E9', (new HexColor('#3E7490'))->tint(0.80)->value);
    }

    public function testShadeAndTintClampWeights(): void
    {
        $color = new HexColor('#3E7490');

        $this->assertSame('#000000', $color->shade(5.0)->value);
        $this->assertSame('#FFFFFF', $color->tint(5.0)->value);
        $this->assertTrue($color->equals($color->shade(-1.0)));
    }

    public function testToRgb(): void
    {
        $this->assertSame([62, 116, 144], (new HexColor('#3E7490'))->toRgb());
    }

    public function testFromRgbRoundTrip(): void
    {
        $color = new HexColor('#C3D300');

        $this->assertTrue($color->equals(HexColor::fromRgb($color->toRgb())));
    }

    public function testContrastRatioOfBlackOnWhiteIsTwentyOne(): void
    {
        $ratio = (new HexColor('#000000'))->contrastRatio(new HexColor('#FFFFFF'));

        $this->assertEqualsWithDelta(21.0, $ratio, 0.001);
    }

    public function testContrastRatioIsSymmetricAndOneForEqualColors(): void
    {
        $a = new HexColor('#3E7490');
        $b = new HexColor('#FFFFFF');

        $this->assertEqualsWithDelta($a->contrastRatio($b), $b->contrastRatio($a), 0.0001);
        $this->assertEqualsWithDelta(1.0, $a->contrastRatio($a), 0.0001);
    }

    public function testEqualsIgnoresCase(): void
    {
        $this->assertTrue((new HexColor('#abcdef'))->equals(new HexColor('#ABCDEF')));
    }

    public function testStringable(): void
    {
        $this->assertSame('#3E7490', (string) new HexColor('#3E7490'));
    }
}
