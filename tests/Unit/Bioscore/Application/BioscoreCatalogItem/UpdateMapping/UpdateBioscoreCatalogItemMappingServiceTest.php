<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\UpdateMapping;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\Ignore\IgnoreBioscoreCatalogItemService;
use App\Bioscore\Application\BioscoreCatalogItem\Map\MapBioscoreCatalogItemService;
use App\Bioscore\Application\BioscoreCatalogItem\Unmap\UnmapBioscoreCatalogItemService;
use App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping\UpdateBioscoreCatalogItemMappingRequest;
use App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping\UpdateBioscoreCatalogItemMappingResponse;
use App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping\UpdateBioscoreCatalogItemMappingService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMapped;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
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
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateBioscoreCatalogItemMappingServiceTest extends TestCase
{
    private InMemoryBioscoreCatalogItemRepository $repository;
    private InMemoryCombustibleRepository $combustibles;
    private string $targetId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = BioscoreCatalogItemRepositoryMother::inMemory();
        $this->combustibles = new InMemoryCombustibleRepository();

        $combustible = CombustibleMother::fromName('Gas natural');
        $this->combustibles->add($combustible);
        $this->targetId = (string) $combustible->id();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testATargetMapsManually(): void
    {
        $item = BioscoreCatalogItemMother::registered();
        $this->repository->add($item);

        $response = $this->update($item, targetId: $this->targetId);

        $this->assertSame($item, $response->item);
        $this->assertSame(BioscoreMappingStatus::ManuallyMapped, $item->status());
        $this->assertSame($this->targetId, $item->targetId());
    }

    public function testConfirmingAnAutoMappedTargetMakesItManual(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: $this->targetId, automatic: true);
        $this->repository->add($item);

        $this->update($item, targetId: $this->targetId);

        $this->assertSame(BioscoreMappingStatus::ManuallyMapped, $item->status());
    }

    public function testResubmittingTheSameManualTargetPublishesNothing(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: $this->targetId);
        $this->repository->add($item);

        $spy = new SpyDomainEventSubscriber();

        $this->update($item, targetId: $this->targetId);

        $this->assertNotInstanceOf(BioscoreCatalogItemMapped::class, $spy->lastEvent());
        $this->assertCount(0, $spy->events());
    }

    public function testAnEmptyTargetUnmapsAMappedItem(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: $this->targetId);
        $this->repository->add($item);

        $this->update($item, targetId: '');

        $this->assertSame(BioscoreMappingStatus::Unmapped, $item->status());
        $this->assertNull($item->targetId());
    }

    public function testIgnoreIgnoresTheItemEvenWithATarget(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: $this->targetId);
        $this->repository->add($item);

        $this->update($item, targetId: $this->targetId, ignore: true);

        $this->assertTrue($item->isIgnored());
        $this->assertNull($item->targetId());
    }

    public function testUntickingIgnoreBringsTheItemBackToUnmapped(): void
    {
        $item = BioscoreCatalogItemMother::ignored();
        $this->repository->add($item);

        $this->update($item, targetId: null, ignore: false);

        $this->assertSame(BioscoreMappingStatus::Unmapped, $item->status());
    }

    public function testAnUnknownItemThrows(): void
    {
        $this->expectException(BioscoreCatalogItemNotFoundException::class);

        $this->createSUT()->__invoke(
            new UpdateBioscoreCatalogItemMappingRequest(itemId: BioscoreCatalogItemId::generate(), targetId: $this->targetId),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $item = BioscoreCatalogItemMother::registered();
        $this->repository->add($item);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(
            new UpdateBioscoreCatalogItemMappingRequest(itemId: $item->id(), targetId: $this->targetId),
            UserMother::ownerUser(),
        );
    }

    private function update(BioscoreCatalogItem $item, ?string $targetId = null, bool $ignore = false): UpdateBioscoreCatalogItemMappingResponse
    {
        return $this->createSUT()->__invoke(
            new UpdateBioscoreCatalogItemMappingRequest(itemId: $item->id(), targetId: $targetId, ignore: $ignore),
            UserMother::adminUser(),
        );
    }

    private function createSUT(): UpdateBioscoreCatalogItemMappingService
    {
        $authorizer = new BioscoreCatalogItemAuthorizer();
        $targetFinder = new CatalogTargetFinder(
            $this->combustibles,
            new InMemoryTransportRepository(),
            new InMemoryRefrigerantGasRepository(),
            new InMemoryElectricityMarketerRepository(),
        );

        return new UpdateBioscoreCatalogItemMappingService(
            repository: $this->repository,
            mapItem: new MapBioscoreCatalogItemService($this->repository, $targetFinder, $authorizer),
            unmapItem: new UnmapBioscoreCatalogItemService($this->repository, $authorizer),
            ignoreItem: new IgnoreBioscoreCatalogItemService($this->repository, $authorizer),
            authorizer: $authorizer,
        );
    }
}
