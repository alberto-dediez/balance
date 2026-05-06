<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class SurplusCauseTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $surplusCause = new SurplusCause(
            id: SurplusCauseId::generate(),
            name: 'surplusCause-name',
        );

        $this->assertCount(0, $surplusCause->translations());
        $surplusCause->translate('translated-name', Locale::English);
        $this->assertCount(1, $surplusCause->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSetted(): void
    {
        $surplusCause = new SurplusCause(
            id: SurplusCauseId::generate(),
            name: 'surplusCause-name',
        );
        $surplusCause->translate('translated-name-en', Locale::English);
        $surplusCause->translate('translated-name-it', Locale::Italian);
        $surplusCause->translate('translated-name-ge', Locale::German);

        $this->assertEquals('surplusCause-name', $surplusCause->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $surplusCause = new SurplusCause(
            id: SurplusCauseId::generate(),
            name: 'surplusCause-name',
        );
        $surplusCause->translate('translated-name-en', Locale::English);
        $surplusCause->translate('translated-name-it', Locale::Italian);
        $surplusCause->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $surplusCause->name());
    }
}
