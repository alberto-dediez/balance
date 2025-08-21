<?php

namespace App\Tests\Unit\Property\Application\Country\Remove;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Country\CountryRemoved;
use App\Property\Application\Country\CountryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Country\Remove\RemoveCountryRequest;
use App\Property\Application\Country\Remove\RemoveCountryService;
use App\Property\Application\Country\Remove\RemoveCountryResponse;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;

class RemoveCountryServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = CountryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $country = CountryMother::random();

        $repository->add($country);

        $request = new RemoveCountryRequest(
            countryId: $country->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveCountryResponse);
        $this->assertTrue($country->id()->equals($result->countryId));
        
        $countryPersisted = $repository->find($result->countryId);

        $this->assertNull($countryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CountryRemoved);
    }

    public function testIsHandle()
    {
        $repository = CountryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $country = CountryMother::random();

        $result = $sut->isHandled(new RemoveCountryRequest(
            countryId: $country->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new RemoveCountryService($repository, new CountryAuthorizer());
    }
}