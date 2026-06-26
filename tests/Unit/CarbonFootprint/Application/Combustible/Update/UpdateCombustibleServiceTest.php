<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible\Update;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Combustible\Update\UpdateCombustibleRequest;
use App\CarbonFootprint\Application\Combustible\Update\UpdateCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleTranslated;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateCombustibleServiceTest extends TestCase
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
        $repository = CombustibleRepositoryMother::inMemory();

        $combustible = CombustibleMother::random();

        $repository->add($combustible);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateCombustibleRequest(
            combustibleId: $combustible->id(),
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $combustiblePersisted = $repository->find($result->combustibleId);

        $this->assertNotNull($combustiblePersisted);
        $this->assertSame(Unit::Piece, $combustiblePersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CombustibleUpdated);
        $this->assertTrue($combustiblePersisted->id()->equals($lastEvent->combustibleId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);

        $combustible = CombustibleMother::random();

        $repository->add($combustible);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateCombustibleRequest(
            combustibleId: $combustible->id(),
            name: 'changedName',
            unit: Unit::Kilogram,
        );

        $result = $sut($request, $user);

        $combustiblePersisted = $repository->find($result->combustibleId);

        $this->assertNotNull($combustiblePersisted);
        $this->assertEquals('changedName', $combustiblePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CombustibleTranslated);
        $this->assertTrue($combustiblePersisted->id()->equals($lastEvent->combustibleId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();

        $combustible = CombustibleMother::random();

        $repository->add($combustible);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateCombustibleRequest(
            combustibleId: $combustible->id(),
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
                ),
            ],
        );

        $result = $sut($request, $user);

        $combustiblePersisted = $repository->find($result->combustibleId);

        $this->assertNotNull($combustiblePersisted);
        $this->assertCount(1, $combustiblePersisted->emissionFactors());

        $persistedFactor = $combustiblePersisted->emissionFactorFor(2024);

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        // The emission factor unit is derived from the combustible's unit, not user input.
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    private function createSUT(CombustibleRepository $repository, Locale $locale = Locale::Spanish): UpdateCombustibleService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateCombustibleService(
            combustibleRepository: $repository,
            authorizer: new CombustibleAuthorizer(),
        );
    }
}
