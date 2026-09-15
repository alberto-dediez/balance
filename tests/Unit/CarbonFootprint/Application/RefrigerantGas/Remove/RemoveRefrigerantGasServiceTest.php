<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas\Remove;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Application\RefrigerantGas\Remove\RemoveRefrigerantGasRequest;
use App\CarbonFootprint\Application\RefrigerantGas\Remove\RemoveRefrigerantGasService;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRemoved;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveRefrigerantGasServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $refrigerantGas = RefrigerantGasMother::registeredRefrigerantGas();

        $repository->add($refrigerantGas);

        $request = new RemoveRefrigerantGasRequest(
            refrigerantGasId: $refrigerantGas->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($refrigerantGas->id()->equals($result->refrigerantGasId));

        $refrigerantGasPersisted = $repository->find($result->refrigerantGasId);

        $this->assertNull($refrigerantGasPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RefrigerantGasRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $refrigerantGas = RefrigerantGasMother::random();

        $result = $sut->isHandled(new RemoveRefrigerantGasRequest(
            refrigerantGasId: $refrigerantGas->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RefrigerantGasRepository $repository): RemoveRefrigerantGasService
    {
        return new RemoveRefrigerantGasService($repository, new RefrigerantGasAuthorizer());
    }
}
