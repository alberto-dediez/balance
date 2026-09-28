<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirectionRequiredException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRegistered;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRemoved;
use App\Materiality\Domain\Model\MaterialityTopic\StandardMaterialityTopicCannotBeRemovedException;
use App\Materiality\Domain\Model\MaterialityTopic\StandardMaterialityTopicNumberRequiredException;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MaterialityTopicTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testRegisterPublishesEventWithPayload(): void
    {
        $materialityTopic = new MaterialityTopic(MaterialityTopicId::generate());

        $materialityTopic->register(
            number: 6,
            scope: MaterialityScope::Scope3,
            direction: MaterialityTopicDirection::Upstream,
            name: 'Viajes de negocios',
            description: 'Descripción',
            position: 6,
            standard: true,
        );

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialityTopicRegistered::class, $lastEvent);
        $this->assertEquals([
            'materialityTopicId' => (string) $materialityTopic->id(),
            'number' => 6,
            'scope' => 'scope_3',
            'direction' => 'upstream',
            'name' => 'Viajes de negocios',
            'description' => 'Descripción',
            'position' => 6,
            'standard' => true,
        ], $lastEvent->payload());
        $this->assertSame(6, $materialityTopic->number());
        $this->assertTrue($materialityTopic->isStandard());
        $this->assertTrue($materialityTopic->isActive());
    }

    public function testScope3WithoutDirectionThrows(): void
    {
        $materialityTopic = new MaterialityTopic(MaterialityTopicId::generate());

        $this->expectException(MaterialityTopicDirectionRequiredException::class);

        $materialityTopic->register(null, MaterialityScope::Scope3, null, 'name', '', 0, false);
    }

    public function testStandardWithoutNumberThrows(): void
    {
        $materialityTopic = new MaterialityTopic(MaterialityTopicId::generate());

        $this->expectException(StandardMaterialityTopicNumberRequiredException::class);

        $materialityTopic->register(null, MaterialityScope::Scope1, null, 'name', '', 0, true);
    }

    public function testScope1DiscardsDirection(): void
    {
        $materialityTopic = new MaterialityTopic(MaterialityTopicId::generate());

        $materialityTopic->register(null, MaterialityScope::Scope1, MaterialityTopicDirection::Downstream, 'name', '', 0, false);

        $this->assertSame(MaterialityScope::Scope1, $materialityTopic->scope());
        $this->assertNull($materialityTopic->direction());
    }

    public function testUpdateWithLocaleKeepsBaseNameAndAddsTranslation(): void
    {
        $materialityTopic = MaterialityTopicMother::fromName('Nombre base');

        $materialityTopic->update(
            scope: MaterialityScope::Scope3,
            direction: MaterialityTopicDirection::Downstream,
            name: 'English name',
            description: 'English description',
            position: 3,
            active: false,
            locale: Locale::English,
        );

        $this->assertSame('Nombre base', $materialityTopic->defaultName());
        $this->assertSame('Nombre base', $materialityTopic->name());
        $this->assertSame(MaterialityTopicDirection::Downstream, $materialityTopic->direction());
        $this->assertFalse($materialityTopic->isActive());
        $this->assertCount(1, $materialityTopic->translations());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('English name', $materialityTopic->name());
        $this->assertSame('English description', $materialityTopic->description());
    }

    public function testRemoveStandardThrows(): void
    {
        $materialityTopic = MaterialityTopicMother::standard(StandardScope3Category::BusinessTravel);

        $this->expectException(StandardMaterialityTopicCannotBeRemovedException::class);

        $materialityTopic->remove();
    }

    public function testRemoveTwicePublishesOneEvent(): void
    {
        $materialityTopic = MaterialityTopicMother::registeredMaterialityTopic();

        $materialityTopic->remove();
        $materialityTopic->remove();

        $removedEvents = array_filter(
            $this->spySubscriber->events(),
            static fn($event) => $event instanceof MaterialityTopicRemoved,
        );

        $this->assertCount(1, $removedEvents);
        $this->assertTrue($materialityTopic->isRemoved());
        $this->assertNotNull($materialityTopic->removedAt());
    }

    public function testStandardScope3CategoryDirection(): void
    {
        foreach (StandardScope3Category::cases() as $category) {
            $expected = $category->value <= 8 ? MaterialityTopicDirection::Upstream : MaterialityTopicDirection::Downstream;

            $this->assertSame($expected, $category->direction());
        }
    }

    public function testStandardScope3CategoryTexts(): void
    {
        $this->assertCount(15, StandardScope3Category::cases());

        foreach (StandardScope3Category::cases() as $category) {
            $this->assertNotEmpty($category->name(Locale::Spanish));
            $this->assertNotEmpty($category->name(Locale::English));
            $this->assertNotEmpty($category->description(Locale::Spanish));
            $this->assertNotEmpty($category->description(Locale::English));
            $this->assertNull($category->name(Locale::French));
            $this->assertNull($category->description(Locale::French));
        }
    }
}
