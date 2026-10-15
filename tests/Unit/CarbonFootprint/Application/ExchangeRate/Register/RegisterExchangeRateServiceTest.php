<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ExchangeRate\Register;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Application\ExchangeRate\Register\RegisterExchangeRateRequest;
use App\CarbonFootprint\Application\ExchangeRate\Register\RegisterExchangeRateService;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryExchangeRateRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterExchangeRateServiceTest extends TestCase
{
    private InMemoryExchangeRateRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = ExchangeRateRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegistersTheExchangeRate(): void
    {
        $response = $this->createSUT()->__invoke($this->request(), UserMother::adminUser());

        $this->assertSame(Unit::UsDollar, $response->exchangeRate->currency());
        $this->assertSame('1.25', $response->exchangeRate->rate());
        $this->assertNotNull($this->repository->find($response->exchangeRate->id()));
    }

    public function testRegisteringTheSameCurrencyTwiceThrows(): void
    {
        $service = $this->createSUT();
        $service->__invoke($this->request(), UserMother::adminUser());

        $this->expectException(\InvalidArgumentException::class);

        $service->__invoke($this->request(), UserMother::adminUser());
    }

    public function testOwnerCannotRegister(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke($this->request(), UserMother::ownerUser());
    }

    private function request(): RegisterExchangeRateRequest
    {
        return new RegisterExchangeRateRequest(
            currency: Unit::UsDollar,
            date: new \DateTimeImmutable('2026-10-14'),
            rate: '1.25',
            fetchedAt: new \DateTimeImmutable('2026-10-14 17:00:00'),
        );
    }

    private function createSUT(): RegisterExchangeRateService
    {
        return new RegisterExchangeRateService($this->repository, new ExchangeRateAuthorizer());
    }
}
