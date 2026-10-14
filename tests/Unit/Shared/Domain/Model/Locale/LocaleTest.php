<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use PHPUnit\Framework\TestCase;

class LocaleTest extends TestCase
{
    public function testListFromParsesPipeSeparatedCodes(): void
    {
        $this->assertSame([Locale::Spanish, Locale::English], Locale::listFrom('es|en'));
    }

    public function testListFromIgnoresUnknownCodesAndTrimsSpaces(): void
    {
        $this->assertSame([Locale::Spanish, Locale::French], Locale::listFrom(' es |xx| fr '));
    }

    public function testListFromEmptyStringReturnsEmptyList(): void
    {
        $this->assertSame([], Locale::listFrom(''));
    }
}
