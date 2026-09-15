<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivityRegistered;
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
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
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

class RegisterElectricityActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor());
        $marketerRepo = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepo->add($marketer);

        $gdoFactor = GdOFactorMother::registeredGdOFactor();
        $gdoFactor->setEmissionFactor(ActivityMother::emissionFactor());
        $gdoFactorRepo = GdOFactorRepositoryMother::inMemory();
        $gdoFactorRepo->add($gdoFactor);

        $selector = new ElectricityEmissionFactorSelector($marketerRepo, $gdoFactorRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterElectricityActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator, $this->mixResolver());

        $result = $sut(new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
            electricityMarketerId: $marketer->id(),
            gdOFactorId: $gdoFactor->id(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(ElectricityActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(ElectricityActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisterWithACountryWithMixStoresTheLocationBasedResult(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $electricityMix = $this->spanishElectricityMix();
        $electricityMixRepo = ElectricityMixRepositoryMother::inMemory();
        $electricityMixRepo->add($electricityMix);

        $sut = $this->createSUT($activityRepo, $electricityMixRepo);

        $result = $sut($this->request(new CountryId('C-ES')), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);

        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertSame(180.0, (float) $activity->locationBasedEmissions()?->co2eq());
        $this->assertSame('1500', $activity->emissions()->co2eq());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(ElectricityActivityRegistered::class, $lastEvent);
        $this->assertSame((string) $electricityMix->id(), (string) $lastEvent->electricityMixId());
    }

    public function testRegisterWithACountryWithoutMixLeavesTheLocationBasedResultEmpty(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $sut = $this->createSUT($activityRepo, ElectricityMixRepositoryMother::inMemory());

        $result = $sut($this->request(new CountryId('C-ES')), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);

        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertNull($activity->locationBasedEmissions());
        $this->assertSame('1500', $activity->emissions()->co2eq());
    }

    public function testRegisterWithoutCountryLeavesTheLocationBasedResultEmpty(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $electricityMixRepo = ElectricityMixRepositoryMother::inMemory();
        $electricityMixRepo->add($this->spanishElectricityMix());

        $sut = $this->createSUT($activityRepo, $electricityMixRepo);

        $result = $sut($this->request(), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);

        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertNull($activity->electricityMixId());
        $this->assertNull($activity->locationBasedEmissions());
        $this->assertSame('1500', $activity->emissions()->co2eq());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new ElectricityEmissionFactorSelector(
            ElectricityMarketerRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
        $sut = new RegisterElectricityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator(), $this->mixResolver());
        $this->expectException(\Throwable::class);
        $sut(new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            quantity: ActivityMother::quantity(Unit::KWh),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function createSUT(
        ActivityRepository $activityRepository,
        ElectricityMixRepository $electricityMixRepository,
    ): RegisterElectricityActivityService {
        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor());
        $marketerRepo = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepo->add($marketer);

        $this->electricityMarketerId = $marketer->id();

        return new RegisterElectricityActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new ElectricityEmissionFactorSelector($marketerRepo, GdOFactorRepositoryMother::inMemory()),
            new EmissionsCalculator(),
            new ElectricityMixEmissionsResolver($electricityMixRepository, new EmissionsCalculator()),
        );
    }

    private ?ElectricityMarketerId $electricityMarketerId = null;

    private function request(?CountryId $countryId = null): RegisterElectricityActivityRequest
    {
        return new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
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
        return new ElectricityMixEmissionsResolver(ElectricityMixRepositoryMother::inMemory(), new EmissionsCalculator());
    }

    public function testIsHandled(): void
    {
        $selector = new ElectricityEmissionFactorSelector(
            ElectricityMarketerRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
        $sut = new RegisterElectricityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator(), $this->mixResolver());
        $this->assertTrue($sut->isHandled(new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            quantity: ActivityMother::quantity(Unit::KWh),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
