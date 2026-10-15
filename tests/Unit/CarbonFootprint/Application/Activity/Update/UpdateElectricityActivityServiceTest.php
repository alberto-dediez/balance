<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivityUpdated;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateElectricityActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = ElectricityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor());
        $marketerRepo = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepo->add($marketer);

        $gdoFactor = GdOFactorMother::registeredGdOFactor();
        $gdoFactor->setEmissionFactor(ActivityMother::emissionFactor());
        $gdoFactorRepo = GdOFactorRepositoryMother::inMemory();
        $gdoFactorRepo->add($gdoFactor);

        $selector = new ElectricityEmissionFactorSelector($marketerRepo, $gdoFactorRepo);
        $sut = new UpdateElectricityActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create(), $this->mixResolver());

        $sut(new UpdateElectricityActivityRequest(
            activityId: $activity->id(),
            electricityMarketerId: $marketer->id(),
            gdOFactorId: $gdoFactor->id(),
            quantity: ActivityMother::quantity(Unit::KWh, 2000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(ElectricityActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUpdateWithACountryWithMixStoresTheLocationBasedResult(): void
    {
        $activity = ElectricityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $electricityMix = $this->spanishElectricityMix();
        $electricityMixRepo = ElectricityMixRepositoryMother::inMemory();
        $electricityMixRepo->add($electricityMix);

        $sut = $this->createSUT($repository, $electricityMixRepo);

        $sut($this->request($activity->id(), new CountryId('C-ES')), $this->ownerOfTheActivity());

        $updated = $repository->findById($activity->id());

        $this->assertInstanceOf(ElectricityActivity::class, $updated);
        $this->assertSame(180.0, (float) $updated->locationBasedEmissions()?->co2eq());
        $this->assertSame('1500', $updated->emissions()->co2eq());
        $this->assertSame((string) $electricityMix->id(), (string) $updated->electricityMixId());
    }

    public function testUpdateWithACountryWithoutMixLeavesTheLocationBasedResultEmpty(): void
    {
        $activity = ElectricityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $sut = $this->createSUT($repository, ElectricityMixRepositoryMother::inMemory());

        $sut($this->request($activity->id(), new CountryId('C-ES')), $this->ownerOfTheActivity());

        $updated = $repository->findById($activity->id());

        $this->assertInstanceOf(ElectricityActivity::class, $updated);
        $this->assertNull($updated->locationBasedEmissions());
        $this->assertSame('1500', $updated->emissions()->co2eq());
    }

    public function testUpdateWithoutCountryLeavesTheLocationBasedResultEmpty(): void
    {
        $activity = ElectricityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $electricityMixRepo = ElectricityMixRepositoryMother::inMemory();
        $electricityMixRepo->add($this->spanishElectricityMix());

        $sut = $this->createSUT($repository, $electricityMixRepo);

        $sut($this->request($activity->id()), $this->ownerOfTheActivity());

        $updated = $repository->findById($activity->id());

        $this->assertInstanceOf(ElectricityActivity::class, $updated);
        $this->assertNull($updated->electricityMixId());
        $this->assertNull($updated->locationBasedEmissions());
        $this->assertSame('1500', $updated->emissions()->co2eq());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = ElectricityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new ElectricityEmissionFactorSelector(
            ElectricityMarketerRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
        $sut = new UpdateElectricityActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create(), $this->mixResolver());
        $this->expectException(\Throwable::class);

        $sut(new UpdateElectricityActivityRequest(
            activityId: $activity->id(),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            quantity: ActivityMother::quantity(Unit::KWh),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new ElectricityEmissionFactorSelector(
            ElectricityMarketerRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
        $sut = new UpdateElectricityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create(), $this->mixResolver());
        $this->assertTrue($sut->isHandled(new UpdateElectricityActivityRequest(
            activityId: ActivityId::generate(),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            quantity: ActivityMother::quantity(Unit::KWh),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }

    private function ownerOfTheActivity(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private ?ElectricityMarketerId $electricityMarketerId = null;

    private function createSUT(
        ActivityRepository $activityRepository,
        ElectricityMixRepository $electricityMixRepository,
    ): UpdateElectricityActivityService {
        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor());
        $marketerRepo = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepo->add($marketer);

        $this->electricityMarketerId = $marketer->id();

        return new UpdateElectricityActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new ElectricityEmissionFactorSelector($marketerRepo, GdOFactorRepositoryMother::inMemory()),
            EmissionsCalculatorMother::create(),
            new ElectricityMixEmissionsResolver($electricityMixRepository, EmissionsCalculatorMother::create()),
        );
    }

    private function request(ActivityId $activityId, ?CountryId $countryId = null): UpdateElectricityActivityRequest
    {
        return new UpdateElectricityActivityRequest(
            activityId: $activityId,
            electricityMarketerId: $this->electricityMarketerId,
            gdOFactorId: null,
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            countryId: $countryId,
        );
    }

    private function spanishElectricityMix(): ElectricityMix
    {
        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());

        $electricityMix->setEmissionFactor(new EmissionFactor(
            co2eq: '0.18',
            co2: '0.18',
            ch4: '0',
            n2o: '0',
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            unit: Unit::KWh,
            source: 'REE',
        ));

        return $electricityMix;
    }

    private function mixResolver(): ElectricityMixEmissionsResolver
    {
        return new ElectricityMixEmissionsResolver(ElectricityMixRepositoryMother::inMemory(), EmissionsCalculatorMother::create());
    }
}
