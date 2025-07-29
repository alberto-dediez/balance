<?php

namespace App\Tests\Unit\Property\Application\Country\Register;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Country\Country;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Domain\Model\Country\CountryRegistered;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Country\Register\RegisterCountryRequest;
use App\Property\Application\Country\Register\RegisterCountryService;
use App\Property\Application\Country\Register\RegisterCountryResponse;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;

class RegisterCountryServiceTest extends TestCase
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

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new RegisterCountryRequest(
            name: 'Spain',
            iso: 'ES'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterCountryResponse);
        $this->assertNotEmpty((string) $result->countryId);
        $this->assertEquals(36, strlen((string) $result->countryId));

        $countryPersisted = $repository->find($result->countryId);

        $this->assertNotNull($countryPersisted);
        $this->assertTrue($countryPersisted instanceof Country);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CountryRegistered);
        $this->assertEquals('Spain', $lastEvent->name());
        $this->assertTrue($countryPersisted->id()->equals($lastEvent->countryId()));
    }

    private function createSUT($repository)
    {
        return new RegisterCountryService($repository, new CountryAuthorizer());
    }
}