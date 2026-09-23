<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Theme;

use App\Shared\Infrastructure\Theme\ClassicTheme;
use App\Shared\Infrastructure\Theme\DefaultTheme;
use PHPUnit\Framework\TestCase;

class ClassicThemeTest extends TestCase
{
    public function testItOnlyChangesTheReportLayout(): void
    {
        $sut = new ClassicTheme(new DefaultTheme());

        $this->assertSame('classic', $sut->slug());
        $this->assertSame('Classic', $sut->name());
        $this->assertEquals((new DefaultTheme())->defaults(), $sut->defaults());
        $this->assertNull($sut->stylesheet());
    }
}
