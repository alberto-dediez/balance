<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\Sync;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\Register\RegisterBioscoreCatalogItemService;
use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsResponse;
use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogEntry;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogSyncCompleted;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Bioscore\Infrastructure\Gateway\InMemoryBioscoreCatalogGateway;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCombustibleRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRefrigerantGasRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRoadTransportRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

class SyncBioscoreCatalogsServiceTest extends TestCase
{
    private InMemoryBioscoreCatalogItemRepository $repository;
    private InMemoryBioscoreCatalogGateway $gateway;
    private InMemoryCombustibleRepository $combustibles;
    private InMemoryRoadTransportRepository $roadTransports;
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = BioscoreCatalogItemRepositoryMother::inMemory();
        $this->gateway = new InMemoryBioscoreCatalogGateway();
        $this->combustibles = new InMemoryCombustibleRepository();
        $this->roadTransports = new InMemoryRoadTransportRepository();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegistersNewItemsAndAutoMapsTheOnlyCandidate(): void
    {
        $combustible = CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::CubicMeter);
        $this->combustibles->add($combustible);
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas Natural', 'm3')]);

        $response = $this->sync();

        $this->assertSame(1, $response->registered);
        $this->assertSame(1, $response->autoMapped);
        $this->assertSame([], $response->newPending);

        $item = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);

        $this->assertNotNull($item);
        $this->assertSame(BioscoreMappingStatus::AutoMapped, $item->status());
        $this->assertSame((string) $combustible->id(), $item->targetId());
    }

    public function testDoesNotAutoMapWithTwoCandidates(): void
    {
        $this->roadTransports->add(RoadTransportMother::from('Gasoil', (string) Uuid::v7(), Unit::Liter, Vehicle::PassengerCars));
        $this->roadTransports->add(RoadTransportMother::from('Gasoil', (string) Uuid::v7(), Unit::Liter, Vehicle::Vans));
        $this->gateway->set(BioscoreCatalog::TransportFuel, [new BioscoreCatalogEntry(3, 'Gasoil', 'l')]);

        $response = $this->sync();

        $this->assertSame(0, $response->autoMapped);
        $this->assertCount(1, $response->newPending);
        $this->assertSame(3, $response->newPending[0]->bioscoreId());
    }

    public function testDoesNotAutoMapWithAnIncompatibleUnit(): void
    {
        $this->combustibles->add(CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::Liter));
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'kg')]);

        $response = $this->sync();

        $this->assertSame(0, $response->autoMapped);
        $this->assertCount(1, $response->newPending);
    }

    public function testAutoMapsWithAnEmptyUnit(): void
    {
        $this->combustibles->add(CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::KWh));
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', '')]);

        $this->assertSame(1, $this->sync()->autoMapped);
    }

    public function testDoesNotTouchManuallyMappedNorIgnoredItems(): void
    {
        $combustible = CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::CubicMeter);
        $this->combustibles->add($combustible);

        $this->gateway->set(BioscoreCatalog::FacilityFuel, [
            new BioscoreCatalogEntry(7, 'Gas natural', 'm3'),
            new BioscoreCatalogEntry(8, 'Gas natural', 'm3'),
        ]);
        $this->sync();

        $manual = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);
        $ignored = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 8);
        $this->assertNotNull($manual);
        $this->assertNotNull($ignored);

        $manual->map((string) $combustible->id(), automatic: false);
        $ignored->ignore();
        $this->repository->add($manual);
        $this->repository->add($ignored);

        $this->sync();

        $this->assertSame(BioscoreMappingStatus::ManuallyMapped, $manual->status());
        $this->assertTrue($ignored->isIgnored());
    }

    public function testUnmapsWhenTheTargetNoLongerExists(): void
    {
        $combustible = CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::CubicMeter);
        $this->combustibles->add($combustible);
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);
        $this->sync();

        $combustible->remove();
        $this->combustibles->add($combustible);

        $response = $this->sync();

        $item = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);
        $this->assertNotNull($item);
        $this->assertSame(BioscoreMappingStatus::Unmapped, $item->status());
        $this->assertCount(1, $response->invalidated);
    }

    public function testRemapsSilentlyWhenAnotherSingleCandidateAppears(): void
    {
        $combustible = CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::CubicMeter);
        $this->combustibles->add($combustible);
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);
        $this->sync();

        $combustible->remove();
        $this->combustibles->add($combustible);

        $replacement = CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::CubicMeter);
        $this->combustibles->add($replacement);

        $response = $this->sync();

        $item = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);
        $this->assertNotNull($item);
        $this->assertSame((string) $replacement->id(), $item->targetId());
        $this->assertSame([], $response->invalidated);
    }

    public function testWithdrawsMissingItemsAndRestoresThemWhenTheyComeBack(): void
    {
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);
        $this->sync();

        $this->gateway->set(BioscoreCatalog::FacilityFuel, []);
        $response = $this->sync();

        $item = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);
        $this->assertNotNull($item);
        $this->assertTrue($item->isWithdrawn());
        $this->assertSame(1, $response->withdrawn);

        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);
        $response = $this->sync();

        $this->assertFalse($item->isWithdrawn());
        $this->assertSame(1, $response->restored);
    }

    public function testAFailedCatalogWithdrawsNothingAndTheOthersAreProcessed(): void
    {
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);
        $this->sync();

        $this->gateway->set(BioscoreCatalog::FacilityFuel, []);
        $this->gateway->fail(BioscoreCatalog::FacilityFuel);
        $this->gateway->set(BioscoreCatalog::RefrigerantGas, [new BioscoreCatalogEntry(11, 'R-410A', 'kg')]);

        $response = $this->sync();

        $item = $this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);
        $this->assertNotNull($item);
        $this->assertFalse($item->isWithdrawn());
        $this->assertSame([BioscoreCatalog::FacilityFuel], $response->failedCatalogs);
        $this->assertNotNull($this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::RefrigerantGas, 11));
    }

    public function testPublishesTheCompletionEventOnlyWhenThereAreNews(): void
    {
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);

        $this->sync();

        $this->assertInstanceOf(BioscoreCatalogSyncCompleted::class, $this->spySubscriber->lastEvent());

        $eventsBefore = count($this->spySubscriber->events());

        $this->sync();

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testRequestingASingleCatalogLimitsTheSynchronization(): void
    {
        $this->gateway->set(BioscoreCatalog::FacilityFuel, [new BioscoreCatalogEntry(7, 'Gas natural', 'm3')]);
        $this->gateway->set(BioscoreCatalog::RefrigerantGas, [new BioscoreCatalogEntry(11, 'R-410A', 'kg')]);

        $response = $this->sync(BioscoreCatalog::RefrigerantGas);

        $this->assertSame(1, $response->registered);
        $this->assertNull($this->repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7));
    }

    public function testOwnerCannotSynchronize(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(new SyncBioscoreCatalogsRequest(), UserMother::ownerUser());
    }

    private function sync(?BioscoreCatalog $catalog = null): SyncBioscoreCatalogsResponse
    {
        return $this->createSUT()->__invoke(
            new SyncBioscoreCatalogsRequest(catalog: $catalog, now: new \DateTimeImmutable('NOW')),
            UserMother::adminUser(),
        );
    }

    private function createSUT(): SyncBioscoreCatalogsService
    {
        $authorizer = new BioscoreCatalogItemAuthorizer();

        return new SyncBioscoreCatalogsService(
            repository: $this->repository,
            gateway: $this->gateway,
            targetFinder: new CatalogTargetFinder(
                $this->combustibles,
                $this->roadTransports,
                new InMemoryRefrigerantGasRepository(),
                new InMemoryElectricityMarketerRepository(),
            ),
            registerItem: new RegisterBioscoreCatalogItemService($this->repository, $authorizer),
            authorizer: $authorizer,
            logger: new NullLogger(),
        );
    }
}
