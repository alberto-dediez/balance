<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceEmissionFactorAdded;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRegistered;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRemoved;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class PurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private ?EmissionFactorGroupId $groupId = null;

    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testDefaultUnitIsEuro(): void
    {
        $purchaseService = new PurchaseService(id: PurchaseServiceId::generate());

        $this->assertSame(Unit::Euro, $purchaseService->unit());
        $this->assertSame(PurchaseServiceCategory::Others, $purchaseService->category());
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $purchaseService = PurchaseServiceMother::fromName('Consultoría');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(PurchaseServiceRegistered::class, $event);
        $this->assertSame('Consultoría', $event->name());
        $this->assertSame(Unit::Euro, $event->unit());
        $this->assertSame(Unit::Euro, $purchaseService->unit());
        $this->assertSame(PurchaseServiceCategory::ProfessionalServices, $purchaseService->category());
    }

    public function testUpdateChangesData(): void
    {
        $purchaseService = PurchaseServiceMother::fromName('Consultoría');

        $purchaseService->update('Publicidad', PurchaseServiceCategory::Marketing, Unit::UsDollar);

        $this->assertInstanceOf(PurchaseServiceUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Publicidad', $purchaseService->name());
        $this->assertSame(PurchaseServiceCategory::Marketing, $purchaseService->category());
        $this->assertSame(Unit::UsDollar, $purchaseService->unit());
        $this->assertNotNull($purchaseService->updatedAt());
    }

    public function testTranslateAddTranslation(): void
    {
        $purchaseService = $this->purchaseService();

        $this->assertCount(0, $purchaseService->translations());
        $purchaseService->translate('translated-name', Locale::English);
        $this->assertCount(1, $purchaseService->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $purchaseService = $this->purchaseService();
        $purchaseService->translate('translated-name-en', Locale::English);
        $purchaseService->translate('translated-name-it', Locale::Italian);

        $this->assertEquals('purchase-service-name', $purchaseService->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $purchaseService = $this->purchaseService();
        $purchaseService->translate('translated-name-en', Locale::English);
        $purchaseService->translate('translated-name-it', Locale::Italian);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $purchaseService->name());
    }

    public function testRemoveMarksAsRemoved(): void
    {
        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $purchaseService->remove();

        $this->assertInstanceOf(PurchaseServiceRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($purchaseService->isRemoved());
        $this->assertNotNull($purchaseService->removedAt());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $purchaseService = $this->purchaseService();

        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertInstanceOf(PurchaseServiceEmissionFactorAdded::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $purchaseService->emissionFactors());
        $this->assertSame('1.5', $purchaseService->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSetSameEmissionFactorDoesNotPublish(): void
    {
        $purchaseService = $this->purchaseService();
        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $eventsCount = count($this->spySubscriber->events());

        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount($eventsCount, $this->spySubscriber->events());
    }

    public function testSetEmissionFactorForSameYearUpdatesIt(): void
    {
        $purchaseService = $this->purchaseService();

        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertInstanceOf(PurchaseServiceEmissionFactorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $purchaseService->emissionFactors());
        $this->assertSame('2.0', $purchaseService->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $purchaseService = $this->purchaseService();

        $this->assertNull($purchaseService->emissionFactorFor(1999, $this->groupId()));
    }

    public function testRemoveEmissionFactor(): void
    {
        $purchaseService = $this->purchaseService();
        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $purchaseService->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $purchaseService->emissionFactors());
    }

    public function testRemoveMissingEmissionFactorDoesNotPublish(): void
    {
        $purchaseService = $this->purchaseService();

        $purchaseService->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $this->spySubscriber->events());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $purchaseService = $this->purchaseService();
        $purchaseService->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $purchaseService->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $purchaseService->emissionFactors());
        $this->assertNull($purchaseService->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $purchaseService->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
        $this->assertSame('3.0', $purchaseService->emissionFactorFor(2025, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $purchaseService = $this->purchaseService();

        $this->expectException(\InvalidArgumentException::class);

        $purchaseService->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $purchaseService = $this->purchaseService();
        $purchaseService->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $purchaseService->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $purchaseService->update('purchase-service-name', PurchaseServiceCategory::Others, Unit::UsDollar);

        $factor2023 = $purchaseService->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $purchaseService->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::UsDollar, $factor2023->emissionFactor()->unit());
        $this->assertSame(Unit::UsDollar, $factor2024->emissionFactor()->unit());
        $this->assertSame('1.0', $factor2023->emissionFactor()->co2eq());
    }

    private function purchaseService(): PurchaseService
    {
        return new PurchaseService(
            id: PurchaseServiceId::generate(),
            name: 'purchase-service-name',
            category: PurchaseServiceCategory::Others,
            unit: Unit::Euro,
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
            unit: Unit::Euro,
            source: 'test-source',
        );
    }

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::defaultId();
    }
}
