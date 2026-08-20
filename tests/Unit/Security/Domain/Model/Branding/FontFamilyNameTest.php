<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\InvalidFontFamilyNameException;
use PHPUnit\Framework\TestCase;

class FontFamilyNameTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function validNames(): array
    {
        return [
            ['Inter'],
            ['Open Sans'],
            ['Noto Sans JP'],
            ['IBM Plex Sans'],
            ['Roboto-Condensed'],
        ];
    }

    /**
     * @dataProvider validNames
     */
    public function testItAcceptsGoogleFontNames(string $value): void
    {
        $this->assertSame($value, (new FontFamilyName($value))->value);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function injectionAttempts(): array
    {
        return [
            [''],
            ['Inter"}'],
            ['Inter;color:red'],
            ['Inter<script>'],
            ["Inter';url(evil)"],
            ['url(evil)'],
            ['Inter"'],
            [' Inter'],
            [str_repeat('a', 101)],
        ];
    }

    /**
     * @dataProvider injectionAttempts
     */
    public function testItRejectsInjectionAttempts(string $value): void
    {
        $this->expectException(InvalidFontFamilyNameException::class);

        new FontFamilyName($value);
    }

    public function testEqualsAndStringable(): void
    {
        $this->assertTrue((new FontFamilyName('Inter'))->equals(new FontFamilyName('Inter')));
        $this->assertFalse((new FontFamilyName('Inter'))->equals(new FontFamilyName('Poppins')));
        $this->assertSame('Inter', (string) new FontFamilyName('Inter'));
    }
}
