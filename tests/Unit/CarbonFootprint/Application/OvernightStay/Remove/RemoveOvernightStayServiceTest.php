<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\Remove;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Application\OvernightStay\Remove\RemoveOvernightStayRequest;
use App\CarbonFootprint\Application\OvernightStay\Remove\RemoveOvernightStayService;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRemoved;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveOvernightStayServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $repository->add($overnightStay);

        $request = new RemoveOvernightStayRequest(
            overnightStayId: $overnightStay->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($overnightStay->id()->equals($result->overnightStayId));

        $overnightStayPersisted = $repository->find($result->overnightStayId);

        $this->assertNull($overnightStayPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof OvernightStayRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $overnightStay = OvernightStayMother::random();

        $result = $sut->isHandled(new RemoveOvernightStayRequest(
            overnightStayId: $overnightStay->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(OvernightStayRepository $repository): RemoveOvernightStayService
    {
        return new RemoveOvernightStayService($repository, new OvernightStayAuthorizer());
    }
}
