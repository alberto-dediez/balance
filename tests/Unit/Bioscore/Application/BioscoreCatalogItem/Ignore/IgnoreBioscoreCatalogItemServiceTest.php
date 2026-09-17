<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\Ignore;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\Ignore\IgnoreBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Ignore\IgnoreBioscoreCatalogItemService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class IgnoreBioscoreCatalogItemServiceTest extends TestCase
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

    public function testIgnoresTheItem(): void
    {
        $item = BioscoreCatalogItemMother::mapped(targetId: 'a-target');
        $this->repository->add($item);

        $response = $this->createSUT()->__invoke(new IgnoreBioscoreCatalogItemRequest($item->id()), UserMother::adminUser());

        $this->assertTrue($response->item->isIgnored());
        $this->assertNull($response->item->targetId());
    }

    public function testUnknownItemThrows(): void
    {
        $this->expectException(BioscoreCatalogItemNotFoundException::class);

        $this->createSUT()->__invoke(new IgnoreBioscoreCatalogItemRequest(BioscoreCatalogItemId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotIgnore(): void
    {
        $item = BioscoreCatalogItemMother::registered();
        $this->repository->add($item);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke(new IgnoreBioscoreCatalogItemRequest($item->id()), UserMother::ownerUser());
    }

    private function createSUT(): IgnoreBioscoreCatalogItemService
    {
        return new IgnoreBioscoreCatalogItemService($this->repository, new BioscoreCatalogItemAuthorizer());
    }
}
