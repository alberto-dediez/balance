<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\Action;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Domain\Model\Locale\Locale;

class ActionTest extends TestCase
{
    public function testTranslateAddTranslation()
    {
        $action = new Action(
            id: ActionId::generate(),
            description: 'action-description'
        );

        $this->assertCount(0, $action->translations());

        $action->translate('translated-description', Locale::English);

        $this->assertCount(1, $action->translations());
    }

    public function testTranslateReturnOriginalDescriptionIfNoLocaleIsSetted()
    {
        $action = new Action(
            id: ActionId::generate(),
            description: 'action-description'
        );
        $action->translate('translated-description-en', Locale::English);
        $action->translate('translated-description-it', Locale::Italian);
        $action->translate('translated-description-ge', Locale::German);

        $this->assertEquals('action-description', $action->description());
    }

    public function testTranslateReturnTranslatedDescription()
    {
        $action = new Action(
            id: ActionId::generate(),
            description: 'action-description'
        );
        $action->translate('translated-description-en', Locale::English);
        $action->translate('translated-description-it', Locale::Italian);
        $action->translate('translated-description-ge', Locale::German);

        $action->translations()->setLocale(Locale::Italian);

        $this->assertEquals('translated-description-it', $action->description());
    }
}