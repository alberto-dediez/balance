<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use PHPUnit\Framework\TestCase;

class WasteTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
        );

        $this->assertCount(0, $waste->translations());

        $waste->translate('translated-name', Locale::English);

        $this->assertCount(1, $waste->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSetted(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
        );
        $waste->translate('translated-name-en', Locale::English);
        $waste->translate('translated-name-it', Locale::Italian);
        $waste->translate('translated-name-ge', Locale::German);

        $this->assertEquals('waste-name', $waste->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
        );
        $waste->translate('translated-name-en', Locale::English);
        $waste->translate('translated-name-it', Locale::Italian);
        $waste->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $waste->name());
    }
}
