<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySettings;

use App\Materiality\Domain\Model\MaterialitySettings\InvalidRelevanceThresholdException;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRegistered;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MaterialitySettingsTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegister(): void
    {
        $materialitySettings = new MaterialitySettings(MaterialitySettingsId::generate());
        $materialitySettings->register(40, false);

        $this->assertSame(40, $materialitySettings->relevanceThresholdPercent());
        $this->assertFalse($materialitySettings->highMagnitudeForcesInclusion());
        $this->assertSame(40, $materialitySettings->rule()->thresholdPercent);
        $this->assertInstanceOf(MaterialitySettingsRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisterRejectsAnInvalidThreshold(): void
    {
        $this->expectException(InvalidRelevanceThresholdException::class);

        (new MaterialitySettings(MaterialitySettingsId::generate()))->register(0, true);
    }

    public function testUpdate(): void
    {
        $materialitySettings = MaterialitySettingsMother::default();

        $materialitySettings->update(70, false);

        $this->assertSame(70, $materialitySettings->relevanceThresholdPercent());
        $this->assertFalse($materialitySettings->highMagnitudeForcesInclusion());
        $this->assertNotNull($materialitySettings->updatedAt());
        $this->assertInstanceOf(MaterialitySettingsUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUpdateIsIdempotent(): void
    {
        $materialitySettings = MaterialitySettingsMother::withThreshold(60, true);
        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $materialitySettings->update(60, true);

        $this->assertNull($spySubscriber->lastEvent());
    }

    public function testUpdateRejectsAnInvalidThreshold(): void
    {
        $this->expectException(InvalidRelevanceThresholdException::class);

        MaterialitySettingsMother::default()->update(101, true);
    }
}
