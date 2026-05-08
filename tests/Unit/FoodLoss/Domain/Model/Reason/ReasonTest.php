<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ReasonTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $reason = new Reason(
            id: ReasonId::generate(),
            description: 'reason-description',
        );

        $this->assertCount(0, $reason->translations());
        $reason->translate('translated-description', Locale::English);
        $this->assertCount(1, $reason->translations());
    }

    public function testTranslateReturnOriginalDescriptionIfNoLocaleIsSetted(): void
    {
        $reason = new Reason(
            id: ReasonId::generate(),
            description: 'reason-description',
        );
        $reason->translate('translated-description-en', Locale::English);
        $reason->translate('translated-description-it', Locale::Italian);
        $reason->translate('translated-description-ge', Locale::German);

        $this->assertEquals('reason-description', $reason->description());
    }

    public function testTranslateReturnTranslatedDescription(): void
    {
        $reason = new Reason(
            id: ReasonId::generate(),
            description: 'reason-description',
        );
        $reason->translate('translated-description-en', Locale::English);
        $reason->translate('translated-description-it', Locale::Italian);
        $reason->translate('translated-description-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-description-it', $reason->description());
    }
}
