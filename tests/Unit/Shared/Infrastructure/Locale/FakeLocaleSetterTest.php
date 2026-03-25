<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use PHPUnit\Framework\TestCase;

class FakeLocaleSetterTest extends TestCase
{
    protected function tearDown(): void
    {
        LocaleGetter::setLocale(Locale::Spanish);
        LocaleGetter::setDefault(Locale::Spanish);
    }

    public function testSetWithDefaults(): void
    {
        FakeLocaleSetter::set();

        $this->assertEquals(Locale::Spanish, LocaleGetter::locale());
        $this->assertEquals(Locale::Spanish, LocaleGetter::default());
    }

    public function testSetLocale(): void
    {
        FakeLocaleSetter::set(locale: Locale::English);

        $this->assertEquals(Locale::English, LocaleGetter::locale());
        $this->assertEquals(Locale::Spanish, LocaleGetter::default());
    }

    public function testSetDefault(): void
    {
        FakeLocaleSetter::set(default: Locale::French);

        $this->assertEquals(Locale::Spanish, LocaleGetter::locale());
        $this->assertEquals(Locale::French, LocaleGetter::default());
    }

    public function testSetBothLocaleAndDefault(): void
    {
        FakeLocaleSetter::set(
            locale: Locale::German,
            default: Locale::Italian
        );

        $this->assertEquals(Locale::German, LocaleGetter::locale());
        $this->assertEquals(Locale::Italian, LocaleGetter::default());
    }

    public function testSetWithTurkish(): void
    {
        FakeLocaleSetter::set(locale: Locale::Turkish, default: Locale::Turkish);

        $this->assertEquals(Locale::Turkish, LocaleGetter::locale());
        $this->assertEquals(Locale::Turkish, LocaleGetter::default());
    }
}
