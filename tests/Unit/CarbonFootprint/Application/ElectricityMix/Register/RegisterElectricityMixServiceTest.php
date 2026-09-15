<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\Register;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixCountryAlreadyRegisteredException;
use App\CarbonFootprint\Application\ElectricityMix\Register\RegisterElectricityMixRequest;
use App\CarbonFootprint\Application\ElectricityMix\Register\RegisterElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRegistered;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterElectricityMixServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterElectricityMixRequest(
            countryId: new CountryId('C-ES'),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->electricityMixId);
        $this->assertEquals(36, strlen((string) $result->electricityMixId));
        $this->assertEquals('C-ES', (string) $result->countryId);

        $electricityMixPersisted = $repository->find($result->electricityMixId);

        $this->assertNotNull($electricityMixPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ElectricityMixRegistered);
        $this->assertEquals('C-ES', (string) $lastEvent->countryId());
        $this->assertTrue($electricityMixPersisted->id()->equals($lastEvent->electricityMixId()));
    }

    public function testRegisterWithAlreadyUsedCountryThrows(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add(ElectricityMixMother::fromCountryAndId('C-ES', 'EM-1'));

        $sut = $this->createSUT($repository);

        $this->expectException(ElectricityMixCountryAlreadyRegisteredException::class);

        $sut(
            new RegisterElectricityMixRequest(countryId: new CountryId('C-ES')),
            UserMother::adminUser(),
        );
    }

    public function testRegisterWithoutCountryThrows(): void
    {
        $sut = $this->createSUT(ElectricityMixRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RegisterElectricityMixRequest(), UserMother::adminUser());
    }

    public function testOwnerCannotRegister(): void
    {
        $sut = $this->createSUT(ElectricityMixRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new RegisterElectricityMixRequest(countryId: new CountryId('C-ES')),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testIsHandle(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterElectricityMixRequest());

        $this->assertTrue($result);
    }

    private function createSUT(ElectricityMixRepository $repository): RegisterElectricityMixService
    {
        return new RegisterElectricityMixService(
            electricityMixRepository: $repository,
            authorizer: new ElectricityMixAuthorizer(),
        );
    }
}
