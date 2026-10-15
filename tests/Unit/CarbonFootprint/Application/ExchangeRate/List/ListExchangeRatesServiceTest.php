<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ExchangeRate\List;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Application\ExchangeRate\List\ListExchangeRatesRequest;
use App\CarbonFootprint\Application\ExchangeRate\List\ListExchangeRatesService;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryExchangeRateRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListExchangeRatesServiceTest extends TestCase
{
    private InMemoryExchangeRateRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = ExchangeRateRepositoryMother::inMemory();
        $this->repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));
        $this->repository->add(ExchangeRateMother::registered(currency: Unit::PoundSterling, rate: '0.80'));
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsTheFilteredExchangeRatesAndAllowsAdminsToSync(): void
    {
        $response = $this->createSUT()->__invoke(new ListExchangeRatesRequest(currency: Unit::PoundSterling), UserMother::adminUser());

        $this->assertCount(1, $response->exchangeRates);
        $this->assertSame(Unit::PoundSterling, $response->exchangeRates[0]->currency());
        $this->assertSame(1, $response->totalResults);
        $this->assertTrue($response->canSync);
    }

    public function testOwnerCannotList(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(new ListExchangeRatesRequest(), UserMother::ownerUser());
    }

    private function createSUT(): ListExchangeRatesService
    {
        return new ListExchangeRatesService($this->repository, new ExchangeRateAuthorizer());
    }
}
