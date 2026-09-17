<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem\Register;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\Register\RegisterBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Register\RegisterBioscoreCatalogItemService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterBioscoreCatalogItemServiceTest extends TestCase
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

    public function testRegistersTheItem(): void
    {
        $response = $this->createSUT()->__invoke($this->request(), UserMother::adminUser());

        $this->assertSame(BioscoreCatalog::FacilityFuel, $response->item->catalog());
        $this->assertSame(7, $response->item->bioscoreId());
        $this->assertNotNull($this->repository->find($response->item->id()));
    }

    public function testRegisteringTheSameCatalogAndBioscoreIdTwiceThrows(): void
    {
        $service = $this->createSUT();
        $service->__invoke($this->request(), UserMother::adminUser());

        $this->expectException(\InvalidArgumentException::class);

        $service->__invoke($this->request(), UserMother::adminUser());
    }

    public function testTheSameBioscoreIdInAnotherCatalogIsAllowed(): void
    {
        $service = $this->createSUT();
        $service->__invoke($this->request(), UserMother::adminUser());

        $response = $service->__invoke($this->request(BioscoreCatalog::TransportFuel), UserMother::adminUser());

        $this->assertSame(BioscoreCatalog::TransportFuel, $response->item->catalog());
    }

    public function testOwnerCannotRegister(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()->__invoke($this->request(), UserMother::ownerUser());
    }

    private function request(BioscoreCatalog $catalog = BioscoreCatalog::FacilityFuel): RegisterBioscoreCatalogItemRequest
    {
        return new RegisterBioscoreCatalogItemRequest(
            catalog: $catalog,
            bioscoreId: 7,
            name: 'Gas Natural',
            unit: 'm3',
            seenAt: new \DateTimeImmutable('2026-01-15 06:00:00'),
        );
    }

    private function createSUT(): RegisterBioscoreCatalogItemService
    {
        return new RegisterBioscoreCatalogItemService($this->repository, new BioscoreCatalogItemAuthorizer());
    }
}
