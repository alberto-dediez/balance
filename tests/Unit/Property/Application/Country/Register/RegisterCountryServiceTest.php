<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Country\Register;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Application\Country\Register\RegisterCountryRequest;
use App\Property\Application\Country\Register\RegisterCountryService;
use App\Property\Domain\Model\Country\CountryRegistered;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterCountryServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = CountryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new RegisterCountryRequest(
            name: 'Spain',
            iso: 'ESP',
            vatRate: 21.0,
            europeanUnion: true,
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->countryId);
        $this->assertEquals(36, strlen((string) $result->countryId));

        $countryPersisted = $repository->find($result->countryId);

        $this->assertNotNull($countryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CountryRegistered);
        $this->assertEquals('Spain', $lastEvent->name());
        $this->assertSame(21.0, $countryPersisted->vatRate());
        $this->assertTrue($countryPersisted->isEuropeanUnion());
        $this->assertTrue($countryPersisted->id()->equals($lastEvent->countryId()));
    }

    public function testIsHandle(): void
    {
        $repository = CountryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterCountryRequest(
            name: 'Country',
            iso: '123',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryCountryRepository $repository): RegisterCountryService
    {
        return new RegisterCountryService($repository, new CountryAuthorizer());
    }
}
