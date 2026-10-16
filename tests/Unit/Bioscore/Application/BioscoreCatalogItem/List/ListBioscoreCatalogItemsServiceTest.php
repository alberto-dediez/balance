<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\List;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\List\ListBioscoreCatalogItemsRequest;
use App\Bioscore\Application\BioscoreCatalogItem\List\ListBioscoreCatalogItemsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCombustibleRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRefrigerantGasRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTransportRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListBioscoreCatalogItemsServiceTest extends TestCase
{
    private InMemoryBioscoreCatalogItemRepository $repository;
    private InMemoryCombustibleRepository $combustibles;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = BioscoreCatalogItemRepositoryMother::inMemory();
        $this->combustibles = new InMemoryCombustibleRepository();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testListsOnlyTheRequestedCatalog(): void
    {
        $this->repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 1));
        $this->repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::TransportFuel, bioscoreId: 2));

        $response = $this->createSUT()->__invoke(
            new ListBioscoreCatalogItemsRequest(catalog: BioscoreCatalog::FacilityFuel, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertCount(1, $response->items);
        $this->assertSame(1, $response->totalResults);
    }

    public function testFiltersByStatus(): void
    {
        $this->repository->add(BioscoreCatalogItemMother::registered(bioscoreId: 1));
        $this->repository->add(BioscoreCatalogItemMother::mapped(targetId: 'a-target', bioscoreId: 2));

        $response = $this->createSUT()->__invoke(
            new ListBioscoreCatalogItemsRequest(catalog: BioscoreCatalog::FacilityFuel, status: BioscoreMappingStatus::Unmapped, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertCount(1, $response->items);
    }

    public function testReturnsThePendingCountAndTheTargets(): void
    {
        $combustible = CombustibleMother::fromName('Gas natural');
        $this->combustibles->add($combustible);

        $this->repository->add(BioscoreCatalogItemMother::registered(bioscoreId: 1));
        $this->repository->add(BioscoreCatalogItemMother::ignored(bioscoreId: 2));

        $response = $this->createSUT()->__invoke(
            new ListBioscoreCatalogItemsRequest(catalog: BioscoreCatalog::FacilityFuel, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $response->pendingCount);
        $this->assertArrayHasKey((string) $combustible->id(), $response->targetsById);
    }

    public function testACatalogIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->__invoke(new ListBioscoreCatalogItemsRequest(), UserMother::adminUser());
    }

    public function testOwnerCannotList(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(
            new ListBioscoreCatalogItemsRequest(catalog: BioscoreCatalog::FacilityFuel),
            UserMother::ownerUser(),
        );
    }

    private function createSUT(): ListBioscoreCatalogItemsService
    {
        return new ListBioscoreCatalogItemsService(
            repository: $this->repository,
            targetFinder: new CatalogTargetFinder(
                $this->combustibles,
                new InMemoryTransportRepository(),
                new InMemoryRefrigerantGasRepository(),
                new InMemoryElectricityMarketerRepository(),
            ),
            authorizer: new BioscoreCatalogItemAuthorizer(),
        );
    }
}
