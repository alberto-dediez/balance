<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility\Update;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Application\Mobility\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Mobility\Update\UpdateMobilityRequest;
use App\CarbonFootprint\Application\Mobility\Update\UpdateMobilityService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityTranslated;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMobilityServiceTest extends TestCase
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
        $repository = MobilityRepositoryMother::inMemory();

        $mobility = MobilityMother::random();

        $repository->add($mobility);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMobilityRequest(
            mobilityId: $mobility->id(),
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertSame(Unit::Piece, $mobilityPersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MobilityUpdated);
        $this->assertTrue($mobilityPersisted->id()->equals($lastEvent->mobilityId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $mobility = MobilityMother::random();

        $repository->add($mobility);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateMobilityRequest(
            mobilityId: $mobility->id(),
            name: 'changedName',
            unit: Unit::Kilometer,
        );

        $result = $sut($request, $user);

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertEquals('changedName', $mobilityPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MobilityTranslated);
        $this->assertTrue($mobilityPersisted->id()->equals($lastEvent->mobilityId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $mobility = MobilityMother::random();

        $repository->add($mobility);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateMobilityRequest(
            mobilityId: $mobility->id(),
            name: 'changedName',
            unit: Unit::Piece,
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

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertCount(1, $mobilityPersisted->emissionFactors());

        $persistedFactor = $mobilityPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    public function testUpdatesTheTravelModeOfTheMobility(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $mobility = MobilityMother::fromNameIdAndTravelMode('Tren', (string) MobilityId::generate(), TravelMode::Transit);

        $repository->add($mobility);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut(
            new UpdateMobilityRequest(
                mobilityId: $mobility->id(),
                name: 'Tren',
                unit: Unit::Kilometer,
                travelMode: TravelMode::Flight,
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertSame(TravelMode::Flight, $mobilityPersisted->travelMode());
    }

    private function createSUT(
        MobilityRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateMobilityService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateMobilityService(
            mobilityRepository: $repository,
            authorizer: new MobilityAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
