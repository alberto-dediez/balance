<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\Remove;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Application\Material\Remove\RemoveMaterialRequest;
use App\CarbonFootprint\Application\Material\Remove\RemoveMaterialService;
use App\CarbonFootprint\Domain\Model\Material\MaterialRemoved;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMaterialServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $material = MaterialMother::registeredMaterial();

        $repository->add($material);

        $request = new RemoveMaterialRequest(
            materialId: $material->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($material->id()->equals($result->materialId));

        $materialPersisted = $repository->find($result->materialId);

        $this->assertNull($materialPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MaterialRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $material = MaterialMother::random();

        $result = $sut->isHandled(new RemoveMaterialRequest(
            materialId: $material->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MaterialRepository $repository): RemoveMaterialService
    {
        return new RemoveMaterialService($repository, new MaterialAuthorizer());
    }
}
