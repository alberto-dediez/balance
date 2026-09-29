<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRegistered;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRemoved;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorTranslated;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MaterialitySectorTest extends TestCase
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
        $materialitySector = new MaterialitySector(MaterialitySectorId::generate());

        $materialitySector->register(name: 'Hoteles');

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialitySectorRegistered::class, $lastEvent);
        $this->assertEquals([
            'materialitySectorId' => (string) $materialitySector->id(),
            'name' => 'Hoteles',
        ], $lastEvent->payload());
        $this->assertSame('Hoteles', $materialitySector->name());
        $this->assertFalse($materialitySector->isRemoved());
    }

    public function testRegisterWithLocaleAddsTranslation(): void
    {
        $materialitySector = new MaterialitySector(MaterialitySectorId::generate());

        $materialitySector->register(name: 'Hotels', locale: Locale::English);

        $this->assertInstanceOf(MaterialitySectorTranslated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $materialitySector->translations());
        $this->assertSame('Hotels', $materialitySector->defaultName());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Hotels', $materialitySector->name());
    }

    public function testUpdateWithoutLocaleChangesBaseName(): void
    {
        $materialitySector = MaterialitySectorMother::fromName('Hoteles');

        $materialitySector->update(name: 'Alojamientos');

        $this->assertInstanceOf(MaterialitySectorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Alojamientos', $materialitySector->defaultName());
        $this->assertNotNull($materialitySector->updatedAt());
        $this->assertCount(0, $materialitySector->translations());
    }

    public function testUpdateWithLocaleKeepsBaseNameAndAddsTranslation(): void
    {
        $materialitySector = MaterialitySectorMother::fromName('Hoteles');

        $materialitySector->update(name: 'Hotels', locale: Locale::English);

        $this->assertSame('Hoteles', $materialitySector->defaultName());
        $this->assertSame('Hoteles', $materialitySector->name());
        $this->assertCount(1, $materialitySector->translations());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Hotels', $materialitySector->name());
        $this->assertSame('Hotels', (string) $materialitySector);
    }

    public function testRemoveTwicePublishesOneEvent(): void
    {
        $materialitySector = MaterialitySectorMother::registeredMaterialitySector();

        $materialitySector->remove();
        $materialitySector->remove();

        $removedEvents = array_filter(
            $this->spySubscriber->events(),
            static fn($event) => $event instanceof MaterialitySectorRemoved,
        );

        $this->assertCount(1, $removedEvents);
        $this->assertTrue($materialitySector->isRemoved());
        $this->assertNotNull($materialitySector->removedAt());
    }
}
