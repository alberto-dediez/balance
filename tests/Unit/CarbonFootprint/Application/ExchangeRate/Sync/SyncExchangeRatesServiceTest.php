<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ExchangeRate\Sync;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Application\ExchangeRate\Register\RegisterExchangeRateService;
use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesRequest;
use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesResponse;
use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesService;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFeedEntry;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFilter;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSyncFailed;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Gateway\InMemoryExchangeRateGateway;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryExchangeRateRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SyncExchangeRatesServiceTest extends TestCase
{
    private InMemoryExchangeRateRepository $repository;
    private InMemoryExchangeRateGateway $gateway;
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = ExchangeRateRepositoryMother::inMemory();
        $this->gateway = new InMemoryExchangeRateGateway();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegistersOneExchangeRatePerKnownCurrencyWithTheFeedDate(): void
    {
        $this->gateway->set(new \DateTimeImmutable('2026-10-14'), [
            new ExchangeRateFeedEntry('USD', '1.1195'),
            new ExchangeRateFeedEntry('GBP', '0.84385'),
        ]);

        $response = $this->sync();

        $this->assertFalse($response->failed);
        $this->assertSame(2, $response->registered);
        $this->assertEquals(new \DateTimeImmutable('2026-10-14'), $response->date);

        $usDollar = $this->repository->findByCurrency(Unit::UsDollar);

        $this->assertNotNull($usDollar);
        $this->assertSame('1.1195', $usDollar->rate());
        $this->assertEquals(new \DateTimeImmutable('2026-10-14'), $usDollar->date());
        $this->assertSame(2, $this->repository->totalResultsByFilter(new ExchangeRateFilter()));
    }

    public function testIgnoresUnknownCurrencies(): void
    {
        $this->gateway->set(new \DateTimeImmutable('2026-10-14'), [
            new ExchangeRateFeedEntry('USD', '1.1195'),
            new ExchangeRateFeedEntry('BGN', '1.9558'),
        ]);

        $response = $this->sync();

        $this->assertSame(1, $response->registered);
        $this->assertSame(['BGN'], $response->unknownCurrencies);
        $this->assertSame(1, $this->repository->totalResultsByFilter(new ExchangeRateFilter()));
    }

    public function testRepeatingTheSameFeedLeavesEverythingUnchanged(): void
    {
        $this->gateway->set(new \DateTimeImmutable('2026-10-14'), [
            new ExchangeRateFeedEntry('USD', '1.1195'),
            new ExchangeRateFeedEntry('GBP', '0.84385'),
        ]);
        $this->sync();
        $eventsBefore = count($this->spySubscriber->events());

        $response = $this->sync();

        $this->assertSame(0, $response->registered);
        $this->assertSame(0, $response->updated);
        $this->assertSame(2, $response->unchanged);
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testRepeatingTheFeedAgainstARateStoredWithTheDatabaseScaleLeavesItUnchanged(): void
    {
        $this->repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar, date: '2026-10-14', rate: '1.11950000'));
        $this->gateway->set(new \DateTimeImmutable('2026-10-14'), [new ExchangeRateFeedEntry('USD', '1.1195')]);
        $eventsBefore = count($this->spySubscriber->events());

        $response = $this->sync();

        $this->assertSame(0, $response->updated);
        $this->assertSame(1, $response->unchanged);
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testANewerFeedUpdatesTheStoredRate(): void
    {
        $this->gateway->set(new \DateTimeImmutable('2026-10-13'), [new ExchangeRateFeedEntry('USD', '1.1100')]);
        $this->sync();
        $this->gateway->set(new \DateTimeImmutable('2026-10-14'), [new ExchangeRateFeedEntry('USD', '1.1195')]);

        $response = $this->sync();

        $this->assertSame(1, $response->updated);
        $this->assertInstanceOf(ExchangeRateUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(1, $this->repository->totalResultsByFilter(new ExchangeRateFilter(currency: Unit::UsDollar)));
        $this->assertSame('1.1195', $this->repository->findByCurrency(Unit::UsDollar)?->rate());
    }

    public function testDiscardsNonPositiveRates(): void
    {
        $this->gateway->set(new \DateTimeImmutable('2026-10-14'), [new ExchangeRateFeedEntry('USD', '0')]);

        $response = $this->sync();

        $this->assertSame(0, $response->registered);
        $this->assertNull($this->repository->findByCurrency(Unit::UsDollar));
    }

    public function testAnUnavailableSourceIsReportedWithoutThrowing(): void
    {
        $this->gateway->fail();

        $response = $this->sync();

        $this->assertTrue($response->failed);
        $this->assertNotNull($response->failure);
        $this->assertSame(0, $this->repository->totalResultsByFilter(new ExchangeRateFilter()));
        $this->assertInstanceOf(ExchangeRateSyncFailed::class, $this->spySubscriber->lastEvent());
    }

    public function testOwnerCannotSync(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->sync(UserMother::ownerUser());
    }

    private function sync(?User $user = null): SyncExchangeRatesResponse
    {
        $authorizer = new ExchangeRateAuthorizer();

        $service = new SyncExchangeRatesService(
            $this->gateway,
            $this->repository,
            new RegisterExchangeRateService($this->repository, $authorizer),
            $authorizer,
            new NullLogger(),
        );

        return $service->__invoke(new SyncExchangeRatesRequest(now: new \DateTimeImmutable('2026-10-14 17:00:00')), $user ?? UserMother::adminUser());
    }
}
