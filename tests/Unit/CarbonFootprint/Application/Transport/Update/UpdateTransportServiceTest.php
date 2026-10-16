<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport\Update;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Application\Transport\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Transport\Update\UpdateTransportRequest;
use App\CarbonFootprint\Application\Transport\Update\UpdateTransportService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\TransportTranslated;
use App\CarbonFootprint\Domain\Model\Transport\TransportUpdated;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateTransportServiceTest extends TestCase
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
        $repository = TransportRepositoryMother::inMemory();

        $transport = TransportMother::random();

        $repository->add($transport);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateTransportRequest(
            transportId: $transport->id(),
            name: 'changedName',
            unit: Unit::Piece,
            vehicle: Vehicle::Trucks,
        );

        $result = $sut($request, $user);

        $transportPersisted = $repository->find($result->transportId);

        $this->assertNotNull($transportPersisted);
        $this->assertSame(Unit::Piece, $transportPersisted->unit());
        $this->assertSame(Vehicle::Trucks, $transportPersisted->vehicle());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TransportUpdated);
        $this->assertTrue($transportPersisted->id()->equals($lastEvent->transportId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);

        $transport = TransportMother::random();

        $repository->add($transport);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateTransportRequest(
            transportId: $transport->id(),
            name: 'changedName',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        );

        $result = $sut($request, $user);

        $transportPersisted = $repository->find($result->transportId);

        $this->assertNotNull($transportPersisted);
        $this->assertEquals('changedName', $transportPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TransportTranslated);
        $this->assertTrue($transportPersisted->id()->equals($lastEvent->transportId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = TransportRepositoryMother::inMemory();

        $transport = TransportMother::random();

        $repository->add($transport);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateTransportRequest(
            transportId: $transport->id(),
            name: 'changedName',
            unit: Unit::Piece,
            vehicle: Vehicle::PassengerCars,
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $result = $sut($request, $user);

        $transportPersisted = $repository->find($result->transportId);

        $this->assertNotNull($transportPersisted);
        $this->assertCount(1, $transportPersisted->emissionFactors());

        $persistedFactor = $transportPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    private function createSUT(
        TransportRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateTransportService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateTransportService(
            transportRepository: $repository,
            authorizer: new TransportAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
