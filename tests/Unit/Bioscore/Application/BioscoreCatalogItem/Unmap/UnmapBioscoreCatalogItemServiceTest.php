<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\Unmap;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\Unmap\UnmapBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Unmap\UnmapBioscoreCatalogItemService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UnmapBioscoreCatalogItemServiceTest extends TestCase
{
    private InMemoryBioscoreCatalogItemRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = BioscoreCatalogItemRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUnmapsTheItem(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target');
        $this->repository->add($item);

        $response = $this->createSUT()->__invoke(new UnmapBioscoreCatalogItemRequest($item->id()), UserMother::adminUser());

        $this->assertSame(BioscoreMappingStatus::Unmapped, $response->item->status());
        $this->assertNull($response->item->targetId());
    }

    public function testUnknownItemThrows(): void
    {
        $this->expectException(BioscoreCatalogItemNotFoundException::class);

        $this->createSUT()->__invoke(new UnmapBioscoreCatalogItemRequest(BioscoreCatalogItemId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotUnmap(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target');
        $this->repository->add($item);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(new UnmapBioscoreCatalogItemRequest($item->id()), UserMother::ownerUser());
    }

    private function createSUT(): UnmapBioscoreCatalogItemService
    {
        return new UnmapBioscoreCatalogItemService($this->repository, new BioscoreCatalogItemAuthorizer());
    }
}
