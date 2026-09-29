<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsRequest;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsResponse;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\ImportMitigationActionsOnCompanyCarbonFootprintMitigationPlanStartedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportMitigationActionsOnCompanyCarbonFootprintMitigationPlanStartedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToMitigationPlanStarted(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new CompanyCarbonFootprintMitigationPlanStarted(CompanyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
    }

    public function testImportsOnlyIfThePlanIsEmpty(): void
    {
        $id = CompanyCarbonFootprintId::generate();
        $service = $this->createMock(ImportMitigationActionsService::class);
        $service->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static fn(ImportMitigationActionsRequest $request): bool => $request->onlyIfEmpty
                && CarbonFootprintReportSubjectType::Company === $request->subjectType
                && (string) $id === $request->carbonFootprintId))
            ->willReturn(new ImportMitigationActionsResponse(0))
        ;

        $this->createSUT($service)->handle(new CompanyCarbonFootprintMitigationPlanStarted($id));
    }

    private function createSUT(?MockObject $service = null): ImportMitigationActionsOnCompanyCarbonFootprintMitigationPlanStartedSubscriber
    {
        /** @var ImportMitigationActionsService&MockObject $importMitigationActions */
        $importMitigationActions = $service ?? $this->createMock(ImportMitigationActionsService::class);

        return new ImportMitigationActionsOnCompanyCarbonFootprintMitigationPlanStartedSubscriber(
            $importMitigationActions,
            new FakeLoggedUser(UserMother::ownerUser()),
        );
    }
}
