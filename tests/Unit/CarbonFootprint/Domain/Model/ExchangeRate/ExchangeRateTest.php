<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRegistered;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ExchangeRateTest extends TestCase
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

    public function testRegisterPublishesTheEventWithItsPayload(): void
    {
        $id = ExchangeRateId::generate();
        $fetchedAt = new \DateTimeImmutable('2026-10-14 17:00:00');
        $exchangeRate = new ExchangeRate(id: $id);

        $exchangeRate->register(Unit::UsDollar, new \DateTimeImmutable('2026-10-14'), '1.25', $fetchedAt);

        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ExchangeRateRegistered::class, $event);
        $this->assertSame([
            'exchangeRateId' => (string) $id,
            'currency' => 'USD',
            'date' => '2026-10-14',
            'rate' => '1.25',
            'fetchedAt' => $fetchedAt->format(\DateTimeInterface::ATOM),
        ], $event->payload());
        $this->assertSame(Unit::UsDollar, $exchangeRate->currency());
        $this->assertEquals(new \DateTimeImmutable('2026-10-14'), $exchangeRate->date());
        $this->assertSame('1.25', $exchangeRate->rate());
        $this->assertEquals($fetchedAt, $exchangeRate->fetchedAt());
        $this->assertNull($exchangeRate->updatedAt());
    }

    public function testRegisterRejectsTheEuro(): void
    {
        $exchangeRate = new ExchangeRate(id: ExchangeRateId::generate());

        $this->expectException(\InvalidArgumentException::class);
        $exchangeRate->register(Unit::Euro, new \DateTimeImmutable('2026-10-14'), '1', new \DateTimeImmutable('NOW'));
    }

    public function testRegisterRejectsAPhysicalUnit(): void
    {
        $exchangeRate = new ExchangeRate(id: ExchangeRateId::generate());

        $this->expectException(\InvalidArgumentException::class);
        $exchangeRate->register(Unit::Kilogram, new \DateTimeImmutable('2026-10-14'), '1.25', new \DateTimeImmutable('NOW'));
    }

    public function testRegisterRejectsANonPositiveRate(): void
    {
        $exchangeRate = new ExchangeRate(id: ExchangeRateId::generate());

        $this->expectException(\InvalidArgumentException::class);
        $exchangeRate->register(Unit::UsDollar, new \DateTimeImmutable('2026-10-14'), '0', new \DateTimeImmutable('NOW'));
    }

    public function testRefreshWithSameDateAndRateDoesNothing(): void
    {
        $exchangeRate = ExchangeRateMother::registered(date: '2026-10-14', rate: '1.25');
        $eventsBefore = count($this->spySubscriber->events());

        $changed = $exchangeRate->refresh(new \DateTimeImmutable('2026-10-14'), '1.25', new \DateTimeImmutable('NOW'));

        $this->assertFalse($changed);
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
        $this->assertNull($exchangeRate->updatedAt());
    }

    public function testRefreshWithTheSameRateAtADifferentScaleDoesNothing(): void
    {
        $exchangeRate = ExchangeRateMother::registered(date: '2026-10-14', rate: '1.25000000');
        $eventsBefore = count($this->spySubscriber->events());

        $changed = $exchangeRate->refresh(new \DateTimeImmutable('2026-10-14'), '1.25', new \DateTimeImmutable('NOW'));

        $this->assertFalse($changed);
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
        $this->assertSame('1.25000000', $exchangeRate->rate());
        $this->assertNull($exchangeRate->updatedAt());
    }

    public function testRefreshWithNewDateAndRatePublishesTheEvent(): void
    {
        $exchangeRate = ExchangeRateMother::registered(date: '2026-10-13', rate: '1.20');
        $fetchedAt = new \DateTimeImmutable('2026-10-14 17:00:00');

        $changed = $exchangeRate->refresh(new \DateTimeImmutable('2026-10-14'), '1.25', $fetchedAt);

        $this->assertTrue($changed);
        $this->assertInstanceOf(ExchangeRateUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertEquals(new \DateTimeImmutable('2026-10-14'), $exchangeRate->date());
        $this->assertSame('1.25', $exchangeRate->rate());
        $this->assertEquals($fetchedAt, $exchangeRate->fetchedAt());
        $this->assertEquals($fetchedAt, $exchangeRate->updatedAt());
    }

    public function testRefreshRejectsANonPositiveRate(): void
    {
        $exchangeRate = ExchangeRateMother::registered();

        $this->expectException(\InvalidArgumentException::class);
        $exchangeRate->refresh(new \DateTimeImmutable('2026-10-15'), '-1', new \DateTimeImmutable('NOW'));
    }

    public function testFactorToEuroIsTheInverseOfTheRate(): void
    {
        $exchangeRate = ExchangeRateMother::registered(rate: '1.25');

        $this->assertEqualsWithDelta(0.8, $exchangeRate->factorToEuro(), 1e-9);
    }
}
