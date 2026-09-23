<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\CompleteCompanyCarbonFootprintOnCompanyCarbonFootprintScopeAnsweredSubscriber;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintScopeAnswered;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompleteCompanyCarbonFootprintOnCompanyCarbonFootprintScopeAnsweredSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToScopeAnswered(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new CompanyCarbonFootprintScopeAnswered(CompanyCarbonFootprintId::generate(), Scope::Scope2)));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
    }

    public function testCompletesTheFootprintWhenScope2IsAnswered(): void
    {
        $service = $this->createMock(CompleteCompanyCarbonFootprintService::class);
        $service->expects($this->once())->method('__invoke');

        $this->createSUT($service)->handle(new CompanyCarbonFootprintScopeAnswered(CompanyCarbonFootprintId::generate(), Scope::Scope2));
    }

    public function testIgnoresScope1(): void
    {
        $service = $this->createMock(CompleteCompanyCarbonFootprintService::class);
        $service->expects($this->never())->method('__invoke');

        $this->createSUT($service)->handle(new CompanyCarbonFootprintScopeAnswered(CompanyCarbonFootprintId::generate(), Scope::Scope1));
    }

    private function createSUT(?MockObject $service = null): CompleteCompanyCarbonFootprintOnCompanyCarbonFootprintScopeAnsweredSubscriber
    {
        /** @var CompleteCompanyCarbonFootprintService&MockObject $completeCompanyCarbonFootprint */
        $completeCompanyCarbonFootprint = $service ?? $this->createMock(CompleteCompanyCarbonFootprintService::class);

        return new CompleteCompanyCarbonFootprintOnCompanyCarbonFootprintScopeAnsweredSubscriber(
            $completeCompanyCarbonFootprint,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
