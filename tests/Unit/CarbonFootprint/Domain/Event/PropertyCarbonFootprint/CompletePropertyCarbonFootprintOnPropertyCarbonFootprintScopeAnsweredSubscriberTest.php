<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\CompletePropertyCarbonFootprintOnPropertyCarbonFootprintScopeAnsweredSubscriber;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintScopeAnswered;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompletePropertyCarbonFootprintOnPropertyCarbonFootprintScopeAnsweredSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToScopeAnswered(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PropertyCarbonFootprintScopeAnswered(PropertyCarbonFootprintId::generate(), Scope::Scope2)));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testCompletesTheFootprintWhenScope2IsAnswered(): void
    {
        $service = $this->createMock(CompletePropertyCarbonFootprintService::class);
        $service->expects($this->once())->method('__invoke');

        $this->createSUT($service)->handle(new PropertyCarbonFootprintScopeAnswered(PropertyCarbonFootprintId::generate(), Scope::Scope2));
    }

    public function testIgnoresScope1(): void
    {
        $service = $this->createMock(CompletePropertyCarbonFootprintService::class);
        $service->expects($this->never())->method('__invoke');

        $this->createSUT($service)->handle(new PropertyCarbonFootprintScopeAnswered(PropertyCarbonFootprintId::generate(), Scope::Scope1));
    }

    private function createSUT(?MockObject $service = null): CompletePropertyCarbonFootprintOnPropertyCarbonFootprintScopeAnsweredSubscriber
    {
        /** @var CompletePropertyCarbonFootprintService&MockObject $completePropertyCarbonFootprint */
        $completePropertyCarbonFootprint = $service ?? $this->createMock(CompletePropertyCarbonFootprintService::class);

        return new CompletePropertyCarbonFootprintOnPropertyCarbonFootprintScopeAnsweredSubscriber(
            $completePropertyCarbonFootprint,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
