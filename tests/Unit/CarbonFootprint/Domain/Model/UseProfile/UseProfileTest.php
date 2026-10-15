<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileEmissionFactorAdded;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRegistered;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRemoved;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UseProfileTest extends TestCase
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
        $useProfile = new UseProfile(id: UseProfileId::generate());

        $this->assertSame(Unit::Piece, $useProfile->unit());
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $useProfile = UseProfileMother::fromName('Lavadora clase A');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(UseProfileRegistered::class, $event);
        $this->assertSame('Lavadora clase A', $event->name());
        $this->assertSame(Unit::Piece, $event->unit());
        $this->assertSame(Unit::Piece, $useProfile->unit());
    }

    public function testUpdateChangesData(): void
    {
        $useProfile = UseProfileMother::fromName('Lavadora clase A');

        $useProfile->update('Frigorífico clase B', Unit::KWh);

        $this->assertInstanceOf(UseProfileUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Frigorífico clase B', $useProfile->name());
        $this->assertSame(Unit::KWh, $useProfile->unit());
        $this->assertNotNull($useProfile->updatedAt());
    }

    public function testTranslateAddTranslation(): void
    {
        $useProfile = $this->useProfile();

        $this->assertCount(0, $useProfile->translations());
        $useProfile->translate('translated-name', Locale::English);
        $this->assertCount(1, $useProfile->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $useProfile = $this->useProfile();
        $useProfile->translate('translated-name-en', Locale::English);
        $useProfile->translate('translated-name-it', Locale::Italian);

        $this->assertEquals('use-profile-name', $useProfile->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $useProfile = $this->useProfile();
        $useProfile->translate('translated-name-en', Locale::English);
        $useProfile->translate('translated-name-it', Locale::Italian);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $useProfile->name());
    }

    public function testRemoveMarksAsRemoved(): void
    {
        $useProfile = UseProfileMother::registeredUseProfile();

        $useProfile->remove();

        $this->assertInstanceOf(UseProfileRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($useProfile->isRemoved());
        $this->assertNotNull($useProfile->removedAt());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $useProfile = $this->useProfile();

        $useProfile->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertInstanceOf(UseProfileEmissionFactorAdded::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $useProfile->emissionFactors());
        $this->assertSame('1.5', $useProfile->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSetSameEmissionFactorDoesNotPublish(): void
    {
        $useProfile = $this->useProfile();
        $useProfile->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $eventsCount = count($this->spySubscriber->events());

        $useProfile->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount($eventsCount, $this->spySubscriber->events());
    }

    public function testSetEmissionFactorForSameYearUpdatesIt(): void
    {
        $useProfile = $this->useProfile();

        $useProfile->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $useProfile->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertInstanceOf(UseProfileEmissionFactorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $useProfile->emissionFactors());
        $this->assertSame('2.0', $useProfile->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $useProfile = $this->useProfile();

        $this->assertNull($useProfile->emissionFactorFor(1999, $this->groupId()));
    }

    public function testRemoveEmissionFactor(): void
    {
        $useProfile = $this->useProfile();
        $useProfile->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $useProfile->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $useProfile->emissionFactors());
    }

    public function testRemoveMissingEmissionFactorDoesNotPublish(): void
    {
        $useProfile = $this->useProfile();

        $useProfile->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $this->spySubscriber->events());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $useProfile = $this->useProfile();
        $useProfile->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $useProfile->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $useProfile->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $useProfile->emissionFactors());
        $this->assertNull($useProfile->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $useProfile->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
        $this->assertSame('3.0', $useProfile->emissionFactorFor(2025, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $useProfile = $this->useProfile();

        $this->expectException(\InvalidArgumentException::class);

        $useProfile->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $useProfile = $this->useProfile();
        $useProfile->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $useProfile->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $useProfile->update('use-profile-name', Unit::KWh);

        $factor2023 = $useProfile->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $useProfile->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::KWh, $factor2023->emissionFactor()->unit());
        $this->assertSame(Unit::KWh, $factor2024->emissionFactor()->unit());
        $this->assertSame('1.0', $factor2023->emissionFactor()->co2eq());
    }

    private function useProfile(): UseProfile
    {
        return new UseProfile(
            id: UseProfileId::generate(),
            name: 'use-profile-name',
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
