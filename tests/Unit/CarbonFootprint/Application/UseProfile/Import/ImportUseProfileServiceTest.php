<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\UseProfile\Import;

use App\CarbonFootprint\Application\UseProfile\Import\ImportUseProfileRequest;
use App\CarbonFootprint\Application\UseProfile\Import\ImportUseProfileService;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportUseProfileServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewUseProfileByNameWithDefaults(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportUseProfileRequest(
            name: 'Lavadora clase A',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $useProfile = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfile);
        $this->assertEquals('Lavadora clase A', $useProfile->name());
        $this->assertEquals(Unit::Piece, $useProfile->unit());

        $emissionFactor = $useProfile->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->emissionFactor()->co2eq());
        $this->assertEquals(Unit::Piece, $emissionFactor->emissionFactor()->unit());
    }

    public function testCreatesNewUseProfileWithMappedUnit(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ImportUseProfileRequest(
                name: 'Frigorífico clase B',
                unit: Unit::KWh->value,
                year: '2024',
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.3',
                n2o: '0.2',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $useProfile = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfile);
        $this->assertEquals(Unit::KWh, $useProfile->unit());
    }

    public function testExplicitUseProfileOnlyUpdatesEmissionFactor(): void
    {
        $repository = UseProfileRepositoryMother::inMemory(Locale::Spanish);
        $existing = UseProfileMother::from(name: 'Lavadora clase A', id: (string) UseProfileId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportUseProfileRequest(
            useProfile: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $useProfile = $repository->find($result->useProfileId);

        $this->assertNotNull($useProfile);
        $this->assertEquals('Lavadora clase A', $useProfile->name());
        $this->assertEquals('3.0', $useProfile->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->emissionFactor()->co2eq());
    }

    public function testThrowsWhenNeitherUseProfileNorNameProvided(): void
    {
        $sut = $this->createSUT(UseProfileRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportUseProfileRequest(year: '2024', emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId()), UserMother::adminUser());
    }

    public function testThrowsWhenEmissionFactorGroupIsMissing(): void
    {
        $sut = $this->createSUT(UseProfileRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportUseProfileRequest(name: 'Lavadora clase A', year: '2024', co2eq: '1.5'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $sut = $this->createSUT(UseProfileRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportUseProfileRequest(name: 'Lavadora clase A'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitUseProfileNotFound(): void
    {
        $sut = $this->createSUT(UseProfileRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportUseProfileRequest(
                useProfile: (string) UseProfileId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(UseProfileRepositoryMother::inMemory(Locale::Spanish));

        $this->assertTrue($sut->isHandled(new ImportUseProfileRequest(name: 'Lavadora clase A', year: '2024')));
    }

    private function createSUT(UseProfileRepository $repository): ImportUseProfileService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportUseProfileService(
            useProfileRepository: $repository,
            authorizer: new UseProfileAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
