<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionSectorLinked;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionSectorUnlinked;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MaterialityQuestionSectorsTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testQuestionWithoutSectorsAppliesToAllSectors(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();

        $this->assertTrue($materialityQuestion->appliesToAllSectors());
        $this->assertSame([], $materialityQuestion->sectorIds());
    }

    public function testLinkSectorIsIdempotent(): void
    {
        $materialityQuestion = MaterialityQuestionMother::random();
        $sectorId = MaterialitySectorId::generate();

        $materialityQuestion->linkSector($sectorId);
        $materialityQuestion->linkSector($sectorId);

        $this->assertCount(1, $this->eventsOf(MaterialityQuestionSectorLinked::class));
        $this->assertCount(1, $materialityQuestion->sectors());
        $this->assertFalse($materialityQuestion->appliesToAllSectors());
    }

    public function testUnlinkNotLinkedSectorDoesNotPublish(): void
    {
        $materialityQuestion = MaterialityQuestionMother::forSectors([MaterialitySectorId::generate()]);

        $materialityQuestion->unlinkSector(MaterialitySectorId::generate());

        $this->assertCount(0, $this->eventsOf(MaterialityQuestionSectorUnlinked::class));
        $this->assertCount(1, $materialityQuestion->sectors());
    }

    public function testSectorIdsReturnsValueObjects(): void
    {
        $sectorId = MaterialitySectorId::generate();
        $materialityQuestion = MaterialityQuestionMother::forSectors([$sectorId]);

        $sectorIds = $materialityQuestion->sectorIds();

        $this->assertCount(1, $sectorIds);
        $this->assertInstanceOf(MaterialitySectorId::class, $sectorIds[0]);
        $this->assertTrue($sectorId->equals($sectorIds[0]));
    }

    public function testUpdateSyncsSectors(): void
    {
        $kept = MaterialitySectorId::generate();
        $dropped = MaterialitySectorId::generate();
        $added = MaterialitySectorId::generate();
        $materialityQuestion = MaterialityQuestionMother::forSectors([$kept, $dropped]);

        $linkedBefore = count($this->eventsOf(MaterialityQuestionSectorLinked::class));

        $materialityQuestion->update(
            criterion: MaterialityCriterion::Magnitude,
            topicId: null,
            text: $materialityQuestion->text(),
            help: $materialityQuestion->help(),
            answerScale: MaterialityAnswerScale::Level,
            weight: 1,
            position: 0,
            active: true,
            sectorIds: [$kept, $added],
        );

        $this->assertEquals([$kept, $added], $materialityQuestion->sectorIds());
        $this->assertCount($linkedBefore + 1, $this->eventsOf(MaterialityQuestionSectorLinked::class));

        $unlinked = $this->eventsOf(MaterialityQuestionSectorUnlinked::class);

        $this->assertCount(1, $unlinked);
        $this->assertTrue($dropped->equals($unlinked[0]->sectorId()));
    }

    public function testUpdateWithoutSectorsUnlinksAll(): void
    {
        $materialityQuestion = MaterialityQuestionMother::forSectors([MaterialitySectorId::generate(), MaterialitySectorId::generate()]);

        $materialityQuestion->update(
            criterion: MaterialityCriterion::Magnitude,
            topicId: null,
            text: $materialityQuestion->text(),
            help: $materialityQuestion->help(),
            answerScale: MaterialityAnswerScale::Level,
            weight: 1,
            position: 0,
            active: true,
        );

        $this->assertTrue($materialityQuestion->appliesToAllSectors());
        $this->assertCount(2, $this->eventsOf(MaterialityQuestionSectorUnlinked::class));
    }

    public function testLinkedEventPayloadAndRebuild(): void
    {
        $sectorId = MaterialitySectorId::generate();
        $materialityQuestion = MaterialityQuestionMother::forSectors([$sectorId]);

        $event = $this->eventsOf(MaterialityQuestionSectorLinked::class)[0];

        $this->assertInstanceOf(MaterialityQuestionSectorLinked::class, $event);
        $this->assertSame((string) $materialityQuestion->id(), $event->payload()['materialityQuestionId']);
        $this->assertSame((string) $sectorId, $event->payload()['sectorId']);

        $payload = $event->payload();
        unset($payload['linkId']);

        $rebuilt = MaterialityQuestionSectorLinked::rebuildFromPayload($event->id(), $event->occurredOn(), $payload);

        $this->assertTrue($sectorId->equals($rebuilt->sectorId()));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    private function eventsOf(string $class): array
    {
        return array_values(array_filter(
            $this->spySubscriber->events(),
            static fn($event) => $event instanceof $class,
        ));
    }
}
