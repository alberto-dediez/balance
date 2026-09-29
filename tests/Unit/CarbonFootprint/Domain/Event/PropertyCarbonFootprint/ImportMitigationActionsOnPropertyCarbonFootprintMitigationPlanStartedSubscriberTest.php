<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsRequest;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsResponse;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\ImportMitigationActionsOnPropertyCarbonFootprintMitigationPlanStartedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportMitigationActionsOnPropertyCarbonFootprintMitigationPlanStartedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToMitigationPlanStarted(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PropertyCarbonFootprintMitigationPlanStarted(PropertyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testImportsOnlyIfThePlanIsEmpty(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        $service = $this->createMock(ImportMitigationActionsService::class);
        $service->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static fn(ImportMitigationActionsRequest $request): bool => $request->onlyIfEmpty
                && CarbonFootprintReportSubjectType::Property === $request->subjectType
                && (string) $id === $request->carbonFootprintId))
            ->willReturn(new ImportMitigationActionsResponse(0))
        ;

        $this->createSUT($service)->handle(new PropertyCarbonFootprintMitigationPlanStarted($id));
    }

    private function createSUT(?MockObject $service = null): ImportMitigationActionsOnPropertyCarbonFootprintMitigationPlanStartedSubscriber
    {
        /** @var ImportMitigationActionsService&MockObject $importMitigationActions */
        $importMitigationActions = $service ?? $this->createMock(ImportMitigationActionsService::class);

        return new ImportMitigationActionsOnPropertyCarbonFootprintMitigationPlanStartedSubscriber(
            $importMitigationActions,
            new FakeLoggedUser(UserMother::ownerUser()),
        );
    }
}
