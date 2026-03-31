<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $action = new Action(
            id: ActionId::generate(),
            description: 'action-description',
        );

        $this->assertCount(0, $action->translations());

        $action->translate('translated-description', Locale::English);

        $this->assertCount(1, $action->translations());
    }

    public function testTranslateReturnOriginalDescriptionIfNoLocaleIsSetted(): void
    {
        $action = new Action(
            id: ActionId::generate(),
            description: 'action-description',
        );
        $action->translate('translated-description-en', Locale::English);
        $action->translate('translated-description-it', Locale::Italian);
        $action->translate('translated-description-ge', Locale::German);

        $this->assertEquals('action-description', $action->description());
    }

    public function testTranslateReturnTranslatedDescription(): void
    {
        $action = new Action(
            id: ActionId::generate(),
            description: 'action-description',
        );
        $action->translate('translated-description-en', Locale::English);
        $action->translate('translated-description-it', Locale::Italian);
        $action->translate('translated-description-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-description-it', $action->description());
    }
}
