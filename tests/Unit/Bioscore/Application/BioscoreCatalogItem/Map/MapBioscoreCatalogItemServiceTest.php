<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\Map;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetNotFoundException;
use App\Bioscore\Application\BioscoreCatalogItem\Map\MapBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Map\MapBioscoreCatalogItemService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCombustibleRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRefrigerantGasRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRoadTransportRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class MapBioscoreCatalogItemServiceTest extends TestCase
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

    public function testMapsTheItemManually(): void
    {
        $combustible = CombustibleMother::fromName('Gas natural');
        $this->combustibles->add($combustible);

        $item = BioscoreCatalogItemMother::registered();
        $this->repository->add($item);

        $response = $this->createSUT()->__invoke(
            new MapBioscoreCatalogItemRequest(itemId: $item->id(), targetId: (string) $combustible->id()),
            UserMother::adminUser(),
        );

        $this->assertSame(BioscoreMappingStatus::ManuallyMapped, $response->item->status());
        $this->assertSame((string) $combustible->id(), $response->item->targetId());
    }

    public function testUnknownItemThrows(): void
    {
        $this->expectException(BioscoreCatalogItemNotFoundException::class);

        $this->createSUT()->__invoke(
            new MapBioscoreCatalogItemRequest(itemId: BioscoreCatalogItemId::generate(), targetId: 'a-target'),
            UserMother::adminUser(),
        );
    }

    public function testUnknownTargetThrows(): void
    {
        $item = BioscoreCatalogItemMother::registered();
        $this->repository->add($item);

        $this->expectException(CatalogTargetNotFoundException::class);

        $this->createSUT()->__invoke(
            new MapBioscoreCatalogItemRequest(itemId: $item->id(), targetId: (string) Uuid::v7()),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotMap(): void
    {
        $item = BioscoreCatalogItemMother::registered();
        $this->repository->add($item);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(
            new MapBioscoreCatalogItemRequest(itemId: $item->id(), targetId: 'a-target'),
            UserMother::ownerUser(),
        );
    }

    private function createSUT(): MapBioscoreCatalogItemService
    {
        return new MapBioscoreCatalogItemService(
            repository: $this->repository,
            targetFinder: new CatalogTargetFinder(
                $this->combustibles,
                new InMemoryRoadTransportRepository(),
                new InMemoryRefrigerantGasRepository(),
                new InMemoryElectricityMarketerRepository(),
            ),
            authorizer: new BioscoreCatalogItemAuthorizer(),
        );
    }
}
