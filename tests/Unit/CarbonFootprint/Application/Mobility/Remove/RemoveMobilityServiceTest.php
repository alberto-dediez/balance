<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility\Remove;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Application\Mobility\Remove\RemoveMobilityRequest;
use App\CarbonFootprint\Application\Mobility\Remove\RemoveMobilityService;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRemoved;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMobilityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $mobility = MobilityMother::registeredMobility();

        $repository->add($mobility);

        $request = new RemoveMobilityRequest(
            mobilityId: $mobility->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($mobility->id()->equals($result->mobilityId));

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNull($mobilityPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MobilityRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $mobility = MobilityMother::random();

        $result = $sut->isHandled(new RemoveMobilityRequest(
            mobilityId: $mobility->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MobilityRepository $repository): RemoveMobilityService
    {
        return new RemoveMobilityService($repository, new MobilityAuthorizer());
    }
}
