<?php

namespace App\Tests\Unit\Waste\Application\Container\Update;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerUpdated;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\Update\UpdateContainerRequest;
use App\Waste\Application\Container\Update\UpdateContainerService;
use App\Waste\Application\Container\Update\UpdateContainerResponse;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;

class UpdateContainerServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = ContainerRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $container->accountId());

        $repository->add($container);

        $sut = $this->createSUT($repository);

        $request = new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Waste,
            measurementMethod: MeasurementMethod::Volume
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateContainerResponse);

        $containerPersisted = $repository->find($result->containerId);

        $this->assertNotNull($containerPersisted);
        $this->assertTrue($containerPersisted instanceof Container);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerUpdated);
        $this->assertEquals('444', (string) $lastEvent->wasteId());
        $this->assertTrue($containerPersisted->id()->equals($lastEvent->containerId()));
    }

    private function createSUT($repository)
    {
        return new UpdateContainerService(
            containerRepository: $repository,
            authorizer: new ContainerAuthorizer()
        );
    }
}