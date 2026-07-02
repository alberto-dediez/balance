<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\Update;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Application\OvernightStay\Update\EmissionFactorData;
use App\CarbonFootprint\Application\OvernightStay\Update\UpdateOvernightStayRequest;
use App\CarbonFootprint\Application\OvernightStay\Update\UpdateOvernightStayService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayTranslated;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateOvernightStayServiceTest extends TestCase
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
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();

        $repository->add($overnightStay);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateOvernightStayRequest(
            overnightStayId: $overnightStay->id(),
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $overnightStayPersisted = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStayPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof OvernightStayUpdated);
        $this->assertTrue($overnightStayPersisted->id()->equals($lastEvent->overnightStayId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);

        $overnightStay = OvernightStayMother::random();

        $repository->add($overnightStay);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateOvernightStayRequest(
            overnightStayId: $overnightStay->id(),
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $overnightStayPersisted = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStayPersisted);
        $this->assertEquals('changedName', $overnightStayPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof OvernightStayTranslated);
        $this->assertTrue($overnightStayPersisted->id()->equals($lastEvent->overnightStayId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();

        $repository->add($overnightStay);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateOvernightStayRequest(
            overnightStayId: $overnightStay->id(),
            name: 'changedName',
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

        $overnightStayPersisted = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStayPersisted);
        $this->assertCount(1, $overnightStayPersisted->emissionFactors());

        $persistedFactor = $overnightStayPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        // co2eq is respected as provided by the user (non-zero), not recomputed from co2/ch4/n2o.
        $this->assertSame('1.5', $persistedFactor->co2eq());
        // The emission factor unit is always Unit::Pernoctacion (hardcoded, no user input).
        $this->assertSame(Unit::Pernoctacion, $persistedFactor->unit());
    }

    private function createSUT(
        OvernightStayRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateOvernightStayService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateOvernightStayService(
            overnightStayRepository: $repository,
            authorizer: new OvernightStayAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
