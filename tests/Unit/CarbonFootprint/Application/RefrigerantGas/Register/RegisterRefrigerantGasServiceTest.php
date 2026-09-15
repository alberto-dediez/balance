<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas\Register;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Application\RefrigerantGas\Register\RegisterRefrigerantGasRequest;
use App\CarbonFootprint\Application\RefrigerantGas\Register\RegisterRefrigerantGasService;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRegistered;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterRefrigerantGasServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterRefrigerantGasRequest(
            name: 'Refrigerant Gas 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->refrigerantGasId);
        $this->assertEquals(36, strlen((string) $result->refrigerantGasId));
        $this->assertEquals('Refrigerant Gas 1', $result->name);

        $refrigerantGasPersisted = $repository->find($result->refrigerantGasId);

        $this->assertNotNull($refrigerantGasPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RefrigerantGasRegistered);
        $this->assertEquals('Refrigerant Gas 1', $lastEvent->name());
        $this->assertTrue($refrigerantGasPersisted->id()->equals($lastEvent->refrigerantGasId()));
    }

    public function testIsHandle(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterRefrigerantGasRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RefrigerantGasRepository $repository): RegisterRefrigerantGasService
    {
        return new RegisterRefrigerantGasService(
            refrigerantGasRepository: $repository,
            authorizer: new RefrigerantGasAuthorizer(),
        );
    }
}
