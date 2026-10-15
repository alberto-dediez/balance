<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\Update;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Application\PurchaseService\Update\EmissionFactorData;
use App\CarbonFootprint\Application\PurchaseService\Update\UpdatePurchaseServiceRequest;
use App\CarbonFootprint\Application\PurchaseService\Update\UpdatePurchaseServiceService;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceTranslated;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdatePurchaseServiceServiceTest extends TestCase
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
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $purchaseService = PurchaseServiceMother::random();

        $repository->add($purchaseService);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdatePurchaseServiceRequest(
            purchaseServiceId: $purchaseService->id(),
            name: 'changedName',
            category: PurchaseServiceCategory::Technology,
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $purchaseServicePersisted = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseServicePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PurchaseServiceUpdated);
        $this->assertTrue($purchaseServicePersisted->id()->equals($lastEvent->purchaseServiceId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $purchaseService = PurchaseServiceMother::random();

        $repository->add($purchaseService);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdatePurchaseServiceRequest(
            purchaseServiceId: $purchaseService->id(),
            name: 'changedName',
            category: PurchaseServiceCategory::ProfessionalServices,
            unit: Unit::Euro,
        );

        $result = $sut($request, $user);

        $purchaseServicePersisted = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseServicePersisted);
        $this->assertEquals('changedName', $purchaseServicePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PurchaseServiceTranslated);
        $this->assertTrue($purchaseServicePersisted->id()->equals($lastEvent->purchaseServiceId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $purchaseService = PurchaseServiceMother::random();

        $repository->add($purchaseService);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdatePurchaseServiceRequest(
            purchaseServiceId: $purchaseService->id(),
            name: 'changedName',
            category: PurchaseServiceCategory::Technology,
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

        $purchaseServicePersisted = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseServicePersisted);
        $this->assertCount(1, $purchaseServicePersisted->emissionFactors());

        $persistedFactor = $purchaseServicePersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->emissionFactor()->unit());
    }

    public function testUnitChangeRestampsEmissionFactors(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $purchaseService = PurchaseServiceMother::random();
        $purchaseService->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: $emissionFactorGroup->id(), unit: Unit::Euro));
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $sut(
            new UpdatePurchaseServiceRequest(
                purchaseServiceId: $purchaseService->id(),
                name: $purchaseService->name(),
                category: PurchaseServiceCategory::Technology,
                unit: Unit::UsDollar,
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

        $persisted = $repository->find($purchaseService->id());

        $this->assertNotNull($persisted);
        $this->assertSame(Unit::UsDollar, $persisted->unit());
        $this->assertSame(Unit::UsDollar, $persisted->emissionFactorFor(2024, $emissionFactorGroup->id())?->emissionFactor()->unit());
    }

    public function testThrowsWhenEmissionFactorGroupNotFound(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();
        $purchaseService = PurchaseServiceMother::random();
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new UpdatePurchaseServiceRequest(
                purchaseServiceId: $purchaseService->id(),
                name: 'changedName',
                category: PurchaseServiceCategory::Technology,
                unit: Unit::Euro,
                emissionFactors: [
                    new EmissionFactorData(year: 2024, co2eq: '1', co2: '1', ch4: '1', n2o: '1', emissionFactorGroupId: (string) PurchaseServiceId::generate()),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();
        $purchaseService = PurchaseServiceMother::random();
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new UpdatePurchaseServiceRequest(
                purchaseServiceId: $purchaseService->id(),
                name: 'changedName',
                category: PurchaseServiceCategory::Technology,
                unit: Unit::Euro,
            ),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(
        PurchaseServiceRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdatePurchaseServiceService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdatePurchaseServiceService(
            purchaseServiceRepository: $repository,
            authorizer: new PurchaseServiceAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
