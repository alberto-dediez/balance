<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemIgnored;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMapped;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRegistered;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRestored;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemUnmapped;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemUpdated;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemWithdrawn;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class BioscoreCatalogItemTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterPublishesTheEventAndSetsTheDates(): void
    {
        $seenAt = new \DateTimeImmutable('2026-01-15 06:00:00');
        $item = new BioscoreCatalogItem(id: BioscoreCatalogItemId::generate());

        $item->register(BioscoreCatalog::FacilityFuel, 7, 'Gas Natural', 'm3', $seenAt);

        $this->assertInstanceOf(BioscoreCatalogItemRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(BioscoreCatalog::FacilityFuel, $item->catalog());
        $this->assertSame(7, $item->bioscoreId());
        $this->assertSame('Gas Natural', $item->name());
        $this->assertSame('m3', $item->unit());
        $this->assertEquals($seenAt, $item->firstSeenAt());
        $this->assertEquals($seenAt, $item->lastSeenAt());
        $this->assertTrue($item->isPending());
    }

    public function testRefreshWithoutChangesDoesNotPublishButUpdatesLastSeenAt(): void
    {
        $item = BioscoreCatalogItemMother::registered(name: 'Gas natural', unit: 'm3');
        $eventsBefore = count($this->spySubscriber->events());
        $seenAt = new \DateTimeImmutable('2026-02-01 06:00:00');

        $item->refresh('Gas natural', 'm3', $seenAt);

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
        $this->assertEquals($seenAt, $item->lastSeenAt());
    }

    public function testRefreshWithChangesPublishesTheEvent(): void
    {
        $item = BioscoreCatalogItemMother::registered(name: 'Gas natural', unit: 'm3');

        $item->refresh('Gas natural licuado', 'kWh', new \DateTimeImmutable('NOW'));

        $this->assertInstanceOf(BioscoreCatalogItemUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Gas natural licuado', $item->name());
        $this->assertSame('kWh', $item->unit());
    }

    public function testRefreshRestoresAWithdrawnItem(): void
    {
        $item = BioscoreCatalogItemMother::withdrawn();

        $item->refresh($item->name(), $item->unit(), new \DateTimeImmutable('NOW'));

        $this->assertInstanceOf(BioscoreCatalogItemRestored::class, $this->spySubscriber->lastEvent());
        $this->assertFalse($item->isWithdrawn());
        $this->assertNull($item->withdrawnAt());
    }

    public function testAutomaticMapping(): void
    {
        $item = BioscoreCatalogItemMother::registered();

        $item->map('a-target-id', automatic: true);

        $this->assertInstanceOf(BioscoreCatalogItemMapped::class, $this->spySubscriber->lastEvent());
        $this->assertSame(BioscoreMappingStatus::AutoMapped, $item->status());
        $this->assertSame('a-target-id', $item->targetId());
        $this->assertTrue($item->isMapped());
        $this->assertFalse($item->isPending());
    }

    public function testManualMapping(): void
    {
        $item = BioscoreCatalogItemMother::registered();

        $item->map('a-target-id', automatic: false);

        $this->assertSame(BioscoreMappingStatus::ManuallyMapped, $item->status());
        $this->assertNotNull($item->mappedAt());
    }

    public function testMappingTwiceToTheSameTargetDoesNotPublish(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target-id', automatic: true);
        $eventsBefore = count($this->spySubscriber->events());

        $item->map('a-target-id', automatic: true);

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testMappingAnAutoMappedItemManuallyPublishes(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target-id', automatic: true);

        $item->map('a-target-id', automatic: false);

        $this->assertInstanceOf(BioscoreCatalogItemMapped::class, $this->spySubscriber->lastEvent());
        $this->assertSame(BioscoreMappingStatus::ManuallyMapped, $item->status());
    }

    public function testUnmapClearsTheTarget(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target-id');

        $item->unmap();

        $this->assertInstanceOf(BioscoreCatalogItemUnmapped::class, $this->spySubscriber->lastEvent());
        $this->assertNull($item->targetId());
        $this->assertSame(BioscoreMappingStatus::Unmapped, $item->status());
        $this->assertNull($item->mappedAt());
    }

    public function testUnmapAnUnmappedItemDoesNotPublish(): void
    {
        $item = BioscoreCatalogItemMother::registered();
        $eventsBefore = count($this->spySubscriber->events());

        $item->unmap();

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testIgnoreClearsTheTarget(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target-id');

        $item->ignore();

        $this->assertInstanceOf(BioscoreCatalogItemIgnored::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($item->isIgnored());
        $this->assertNull($item->targetId());
        $this->assertFalse($item->isPending());
    }

    public function testIgnoreIsIdempotent(): void
    {
        $item = BioscoreCatalogItemMother::ignored();
        $eventsBefore = count($this->spySubscriber->events());

        $item->ignore();

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testWithdrawAndRestoreAreIdempotent(): void
    {
        $at = new \DateTimeImmutable('NOW');
        $item = BioscoreCatalogItemMother::registered();

        $item->withdraw($at);

        $this->assertInstanceOf(BioscoreCatalogItemWithdrawn::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($item->isWithdrawn());
        $this->assertFalse($item->isPending());

        $eventsBefore = count($this->spySubscriber->events());
        $item->withdraw($at);
        $this->assertCount($eventsBefore, $this->spySubscriber->events());

        $item->restore($at);
        $this->assertInstanceOf(BioscoreCatalogItemRestored::class, $this->spySubscriber->lastEvent());

        $eventsBefore = count($this->spySubscriber->events());
        $item->restore($at);
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }
}
