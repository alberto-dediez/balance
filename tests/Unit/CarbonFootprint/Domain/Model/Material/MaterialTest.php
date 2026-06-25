<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MaterialTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $material = new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $material->translations());
        $material->translate('translated-name', Locale::English);
        $this->assertCount(1, $material->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $material = new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );
        $material->translate('translated-name-en', Locale::English);
        $material->translate('translated-name-it', Locale::Italian);
        $material->translate('translated-name-ge', Locale::German);

        $this->assertEquals('material-name', $material->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $material = new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );
        $material->translate('translated-name-en', Locale::English);
        $material->translate('translated-name-it', Locale::Italian);
        $material->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $material->name());
    }
}
