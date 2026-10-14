<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Country\Update;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Application\Country\Update\UpdateCountryRequest;
use App\Property\Application\Country\Update\UpdateCountryService;
use App\Property\Domain\Model\Country\CountryUpdated;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateCountryServiceTest extends TestCase
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

        $country = CountryMother::fromName('MyCountry');

        $repository->add($country);

        $sut = $this->createSUT($repository);

        $request = new UpdateCountryRequest(
            countryId: $country->id(),
            name: 'Spain',
            iso: '6666',
            vatRate: 21.0,
            europeanUnion: true,
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $countryPersisted = $repository->find($result->countryId);

        $this->assertNotNull($countryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CountryUpdated);
        $this->assertEquals('Spain', $lastEvent->name());
        $this->assertEquals('6666', (string) $lastEvent->iso());
        $this->assertSame(21.0, $countryPersisted->vatRate());
        $this->assertTrue($countryPersisted->isEuropeanUnion());
        $this->assertTrue($countryPersisted->id()->equals($lastEvent->countryId()));
    }

    public function testIsHandle(): void
    {
        $repository = CountryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $country = CountryMother::random();

        $result = $sut->isHandled(new UpdateCountryRequest(
            countryId: $country->id(),
            name: 'Country',
            iso: '123',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryCountryRepository $repository): UpdateCountryService
    {
        return new UpdateCountryService($repository, new CountryAuthorizer());
    }
}
