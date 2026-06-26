<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\Update;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Application\MachineryFuel\Update\EmissionFactorData;
use App\CarbonFootprint\Application\MachineryFuel\Update\UpdateMachineryFuelRequest;
use App\CarbonFootprint\Application\MachineryFuel\Update\UpdateMachineryFuelService;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelTranslated;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelUpdated;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMachineryFuelServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();

        $repository->add($machineryFuel);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMachineryFuelRequest(
            machineryFuelId: $machineryFuel->id(),
            name: 'changedName',
            unit: Unit::Liter,
            machineryType: MachineryType::Forestry,
        );

        $result = $sut($request, $user);

        $persisted = $repository->find($result->machineryFuelId);

        $this->assertNotNull($persisted);
        $this->assertSame(MachineryType::Forestry, $persisted->machineryType());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MachineryFuelUpdated);
        $this->assertTrue($persisted->id()->equals($lastEvent->machineryFuelId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $machineryFuel = MachineryFuelMother::random();

        $repository->add($machineryFuel);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateMachineryFuelRequest(
            machineryFuelId: $machineryFuel->id(),
            name: 'changedName',
            unit: Unit::Liter,
            machineryType: MachineryType::Agricultural,
        );

        $result = $sut($request, $user);

        $persisted = $repository->find($result->machineryFuelId);

        $this->assertNotNull($persisted);
        $this->assertEquals('changedName', $persisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MachineryFuelTranslated);
        $this->assertTrue($persisted->id()->equals($lastEvent->machineryFuelId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();

        $repository->add($machineryFuel);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMachineryFuelRequest(
            machineryFuelId: $machineryFuel->id(),
            name: 'changedName',
            unit: Unit::Liter,
            machineryType: MachineryType::Agricultural,
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $result = $sut($request, $user);

        $persisted = $repository->find($result->machineryFuelId);

        $this->assertNotNull($persisted);
        $this->assertCount(1, $persisted->emissionFactors());

        $persistedFactor = $persisted->emissionFactorFor(2024);

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::Liter, $persistedFactor->unit());
    }

    private function createSUT(MachineryFuelRepository $repository, Locale $locale = Locale::Spanish): UpdateMachineryFuelService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateMachineryFuelService(
            machineryFuelRepository: $repository,
            authorizer: new MachineryFuelAuthorizer(),
        );
    }
}
