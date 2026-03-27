<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Measurement\Remove;

use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementRequest;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementService;
use App\Waste\Domain\Model\Measurement\MeasurementRemoved;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryMeasurementRepository;
use PHPUnit\Framework\TestCase;

class RemoveMeasurementServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $measurement = MeasurementMother::random();

        $accountId = $measurement->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $repository->add($measurement);

        $request = new RemoveMeasurementRequest(
            measurementId: $measurement->id(),
        );

        $result = $sut($request, $user);

        $this->assertTrue($measurement->id()->equals($result->measurementId));

        $measurementPersisted = $repository->find($result->measurementId);

        $this->assertNull($measurementPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MeasurementRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $measurement = MeasurementMother::random();

        $result = $sut->isHandled(new RemoveMeasurementRequest(
            measurementId: $measurement->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryMeasurementRepository $repository): RemoveMeasurementService
    {
        return new RemoveMeasurementService($repository, new MeasurementAuthorizer());
    }
}
