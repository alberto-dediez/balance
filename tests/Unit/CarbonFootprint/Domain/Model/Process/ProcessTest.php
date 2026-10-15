<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessEmissionFactorAdded;
use App\CarbonFootprint\Domain\Model\Process\ProcessEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRegistered;
use App\CarbonFootprint\Domain\Model\Process\ProcessRemoved;
use App\CarbonFootprint\Domain\Model\Process\ProcessUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private ?EmissionFactorGroupId $groupId = null;

    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testDefaultUnitIsPiece(): void
    {
        $process = new Process(id: ProcessId::generate());

        $this->assertSame(Unit::Piece, $process->unit());
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $process = ProcessMother::fromName('Mecanizado de pieza');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(ProcessRegistered::class, $event);
        $this->assertSame('Mecanizado de pieza', $event->name());
        $this->assertSame(Unit::Piece, $event->unit());
        $this->assertSame(Unit::Piece, $process->unit());
    }

    public function testUpdateChangesData(): void
    {
        $process = ProcessMother::fromName('Mecanizado de pieza');

        $process->update('Envasado', Unit::KWh);

        $this->assertInstanceOf(ProcessUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Envasado', $process->name());
        $this->assertSame(Unit::KWh, $process->unit());
        $this->assertNotNull($process->updatedAt());
    }

    public function testTranslateAddTranslation(): void
    {
        $process = $this->process();

        $this->assertCount(0, $process->translations());
        $process->translate('translated-name', Locale::English);
        $this->assertCount(1, $process->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $process = $this->process();
        $process->translate('translated-name-en', Locale::English);
        $process->translate('translated-name-it', Locale::Italian);

        $this->assertEquals('process-name', $process->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $process = $this->process();
        $process->translate('translated-name-en', Locale::English);
        $process->translate('translated-name-it', Locale::Italian);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $process->name());
    }

    public function testRemoveMarksAsRemoved(): void
    {
        $process = ProcessMother::registeredProcess();

        $process->remove();

        $this->assertInstanceOf(ProcessRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($process->isRemoved());
        $this->assertNotNull($process->removedAt());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $process = $this->process();

        $process->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertInstanceOf(ProcessEmissionFactorAdded::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $process->emissionFactors());
        $this->assertSame('1.5', $process->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSetSameEmissionFactorDoesNotPublish(): void
    {
        $process = $this->process();
        $process->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $eventsCount = count($this->spySubscriber->events());

        $process->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount($eventsCount, $this->spySubscriber->events());
    }

    public function testSetEmissionFactorForSameYearUpdatesIt(): void
    {
        $process = $this->process();

        $process->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $process->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertInstanceOf(ProcessEmissionFactorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $process->emissionFactors());
        $this->assertSame('2.0', $process->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $process = $this->process();

        $this->assertNull($process->emissionFactorFor(1999, $this->groupId()));
    }

    public function testRemoveEmissionFactor(): void
    {
        $process = $this->process();
        $process->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $process->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $process->emissionFactors());
    }

    public function testRemoveMissingEmissionFactorDoesNotPublish(): void
    {
        $process = $this->process();

        $process->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $this->spySubscriber->events());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $process = $this->process();
        $process->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $process->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $process->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $process->emissionFactors());
        $this->assertNull($process->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $process->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
        $this->assertSame('3.0', $process->emissionFactorFor(2025, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $process = $this->process();

        $this->expectException(\InvalidArgumentException::class);

        $process->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $process = $this->process();
        $process->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $process->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $process->update('process-name', Unit::KWh);

        $factor2023 = $process->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $process->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::KWh, $factor2023->emissionFactor()->unit());
        $this->assertSame(Unit::KWh, $factor2024->emissionFactor()->unit());
        $this->assertSame('1.0', $factor2023->emissionFactor()->co2eq());
    }

    private function process(): Process
    {
        return new Process(
            id: ProcessId::generate(),
            name: 'process-name',
            unit: Unit::Piece,
        );
    }

    private function emissionFactor(int $year, string $co2eq): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: '0.1',
            ch4: '0.2',
            n2o: '0.3',
            year: $year,
            emissionFactorGroupId: $this->groupId(),
            unit: Unit::Piece,
            source: 'test-source',
        );
    }

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::defaultId();
    }
}
