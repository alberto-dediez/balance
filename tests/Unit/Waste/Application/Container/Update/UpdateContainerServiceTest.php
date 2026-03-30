<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Container\Update;

use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Application\Container\Update\UpdateContainerRequest;
use App\Waste\Application\Container\Update\UpdateContainerResponse;
use App\Waste\Application\Container\Update\UpdateContainerService;
use App\Waste\Domain\Model\Container\ContainerUpdated;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\NotValidVolumeException;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;
use PHPUnit\Framework\TestCase;

class UpdateContainerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $accountId = $container->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $repository->add($container);

        $sut = $this->createSUT($repository);

        $request = new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Waste,
            measurementMethod: MeasurementMethod::Volume,
            volume: (string) 46,
            wasteManagerId: WasteManagerId::generate(),
        );

        $result = $sut($request, $user);

        $containerPersisted = $repository->find($result->containerId);

        $this->assertNotNull($containerPersisted);
        $this->assertEquals('46', $containerPersisted->volume());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerUpdated);
        $this->assertEquals('444', (string) $lastEvent->wasteId());
        $this->assertTrue($containerPersisted->id()->equals($lastEvent->containerId()));
        $wasteManagerId = $containerPersisted->wasteManagerId();
        self::assertNotNull($wasteManagerId);
        $this->assertTrue($request->wasteManagerId->equals($wasteManagerId));
        $this->assertEquals('46', $lastEvent->volume());
    }

    public function testUpdateWeightContainer(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $accountId = $container->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $repository->add($container);

        $sut = $this->createSUT($repository);

        $request = new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Waste,
            measurementMethod: MeasurementMethod::Weight,
            volume: null,
            wasteManagerId: WasteManagerId::generate(),
        );

        $result = $sut($request, $user);

        $this->assertInstanceOf(UpdateContainerResponse::class, $result);
    }

    public function testUpdateVolumeContainerWithoutVolume(): void
    {
        $this->expectException(NotValidVolumeException::class);

        $repository = ContainerRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $accountId = $container->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $repository->add($container);

        $sut = $this->createSUT($repository);

        $request = new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Waste,
            measurementMethod: MeasurementMethod::Volume,
            volume: null,
            wasteManagerId: WasteManagerId::generate(),
        );

        $sut($request, $user);
    }

    public function testIsHandle(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $container = ContainerMother::random();

        $result = $sut->isHandled(new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Waste,
            measurementMethod: MeasurementMethod::Volume,
            volume: null,
            wasteManagerId: WasteManagerId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryContainerRepository $repository): UpdateContainerService
    {
        return new UpdateContainerService(
            containerRepository: $repository,
            authorizer: new ContainerAuthorizer(),
        );
    }
}
