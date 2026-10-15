<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\UseProfile\Update;

use App\CarbonFootprint\Application\UseProfile\Update\EmissionFactorData;
use App\CarbonFootprint\Application\UseProfile\Update\UpdateUseProfileRequest;
use App\CarbonFootprint\Application\UseProfile\Update\UpdateUseProfileService;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileTranslated;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileUpdated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateUseProfileServiceTest extends TestCase
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
        $repository = UseProfileRepositoryMother::inMemory();

        $useProfile = UseProfileMother::random();

        $repository->add($useProfile);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateUseProfileRequest(
            useProfileId: $useProfile->id(),
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $useProfilePersisted = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfilePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UseProfileUpdated);
        $this->assertTrue($useProfilePersisted->id()->equals($lastEvent->useProfileId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $useProfile = UseProfileMother::random();

        $repository->add($useProfile);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateUseProfileRequest(
            useProfileId: $useProfile->id(),
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $useProfilePersisted = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfilePersisted);
        $this->assertEquals('changedName', $useProfilePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UseProfileTranslated);
        $this->assertTrue($useProfilePersisted->id()->equals($lastEvent->useProfileId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();

        $useProfile = UseProfileMother::random();

        $repository->add($useProfile);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateUseProfileRequest(
            useProfileId: $useProfile->id(),
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

        $useProfilePersisted = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfilePersisted);
        $this->assertCount(1, $useProfilePersisted->emissionFactors());

        $persistedFactor = $useProfilePersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->emissionFactor()->unit());
    }

    public function testUnitChangeRestampsEmissionFactors(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $useProfile = UseProfileMother::random();
        $useProfile->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: $emissionFactorGroup->id(), unit: Unit::Piece));
        $repository->add($useProfile);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $sut(
            new UpdateUseProfileRequest(
                useProfileId: $useProfile->id(),
                name: $useProfile->name(),
                unit: Unit::KWh,
                emissionFactors: [
                    new EmissionFactorData(
                        year: 2024,
                        co2eq: '1.5',
                        co2: '1.0',
                        ch4: '0.2',
                        n2o: '0.3',
                        emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                    ),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );

        $persisted = $repository->find($useProfile->id());

        $this->assertNotNull($persisted);
        $this->assertSame(Unit::KWh, $persisted->unit());
        $this->assertSame(Unit::KWh, $persisted->emissionFactorFor(2024, $emissionFactorGroup->id())?->emissionFactor()->unit());
    }

    public function testThrowsWhenEmissionFactorGroupNotFound(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();
        $useProfile = UseProfileMother::random();
        $repository->add($useProfile);

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new UpdateUseProfileRequest(
                useProfileId: $useProfile->id(),
                name: 'changedName',
                unit: Unit::Piece,
                emissionFactors: [
                    new EmissionFactorData(year: 2024, co2eq: '1', co2: '1', ch4: '1', n2o: '1', emissionFactorGroupId: (string) UseProfileId::generate()),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();
        $useProfile = UseProfileMother::random();
        $repository->add($useProfile);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new UpdateUseProfileRequest(
                useProfileId: $useProfile->id(),
                name: 'changedName',
                unit: Unit::Piece,
            ),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(
        UseProfileRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateUseProfileService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateUseProfileService(
            useProfileRepository: $repository,
            authorizer: new UseProfileAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
