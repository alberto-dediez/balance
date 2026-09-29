<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MitigationMeasureCategoryTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
    }

    public function testTranslateReturnsTheTranslatedName(): void
    {
        $category = MitigationMeasureCategoryMother::fromName('Energía');
        $category->translate('Energy', Locale::English);

        $this->assertSame('Energía', $category->name());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Energy', $category->name());
    }

    public function testUpdateWithLocaleKeepsTheDefaultName(): void
    {
        $category = MitigationMeasureCategoryMother::fromName('Energía');

        $category->update('Energy', Locale::English);

        $this->assertSame('Energía', $category->name());
        $this->assertCount(1, $category->translations());
    }

    public function testRemove(): void
    {
        $spy = new SpyDomainEventSubscriber();
        $category = MitigationMeasureCategoryMother::fromName('Energía');

        $category->remove();

        $this->assertTrue($category->isRemoved());
        $this->assertInstanceOf(MitigationMeasureCategoryRemoved::class, $spy->lastEvent());
    }
}
