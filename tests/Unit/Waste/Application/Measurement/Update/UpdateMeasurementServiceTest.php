<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Measurement\Update;

use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use App\Waste\Application\Measurement\Update\UpdateMeasurementService;
use App\Waste\Domain\Model\Measurement\MeasurementUpdated;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryMeasurementRepository;
use PHPUnit\Framework\TestCase;

class UpdateMeasurementServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $measurement = MeasurementMother::random();

        $repository->add($measurement);

        $accountId = $measurement->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $sut = $this->createSUT($repository);

        $request = new UpdateMeasurementRequest(
            measurementId: $measurement->id(),
            quantity: 2,
            load: 0.26,
            justification: null,
        );

        $result = $sut($request, $user);

        $measurementPersisted = $repository->find($result->measurementId);

        $this->assertNotNull($measurementPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MeasurementUpdated);
        $this->assertEquals('2', $lastEvent->quantity());
        $this->assertTrue($measurementPersisted->id()->equals($lastEvent->measurementId()));
    }

    public function testIsHandle(): void
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $measurement = MeasurementMother::random();

        $result = $sut->isHandled(new UpdateMeasurementRequest(
            measurementId: $measurement->id(),
            quantity: 2,
            load: 0.26,
            justification: null,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryMeasurementRepository $repository): UpdateMeasurementService
    {
        return new UpdateMeasurementService(
            measurementRepository: $repository,
            authorizer: new MeasurementAuthorizer(),
        );
    }
}
