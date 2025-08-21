<?php

namespace App\Tests\Unit\Property\Application\Country\Update;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Country\Country;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Country\CountryUpdated;
use App\Property\Application\Country\CountryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Country\Update\UpdateCountryRequest;
use App\Property\Application\Country\Update\UpdateCountryService;
use App\Property\Application\Country\Update\UpdateCountryResponse;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;

class UpdateCountryServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = CountryRepositoryMother::inMemory();

        $country = CountryMother::fromName('MyCountry');

        $repository->add($country);

        $sut = $this->createSUT($repository);

        $request = new UpdateCountryRequest(
            countryId: $country->id(),
            name: 'Spain',
            iso: '6666'
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateCountryResponse);

        $countryPersisted = $repository->find($result->countryId);

        $this->assertNotNull($countryPersisted);
        $this->assertTrue($countryPersisted instanceof Country);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CountryUpdated);
        $this->assertEquals('Spain', $lastEvent->name());
        $this->assertEquals('6666', (string) $lastEvent->iso());
        $this->assertTrue($countryPersisted->id()->equals($lastEvent->countryId()));
    }

    public function testIsHandle()
    {
        $repository = CountryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $country = CountryMother::random();

        $result = $sut->isHandled(new UpdateCountryRequest(
            countryId: $country->id(),
            name: 'Country',
            iso: '123'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new UpdateCountryService($repository, new CountryAuthorizer());
    }
}