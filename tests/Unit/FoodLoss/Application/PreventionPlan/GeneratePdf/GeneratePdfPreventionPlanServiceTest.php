<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\GeneratePdf;

use App\FoodLoss\Application\PreventionPlan\GeneratePdf\GeneratePdfPreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\GeneratePdf\GeneratePdfPreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanPdfGenerator;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Application\PreventionPlan\NullPreventionPlanDraftTranslator;
use App\Tests\Unit\FoodLoss\Application\PreventionPlan\NullPreventionPlanPdfUploader;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\SpyPreventionPlanPdfGenerator;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GeneratePdfPreventionPlanServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(PreventionPlanRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new GeneratePdfPreventionPlanRequest(
            preventionPlanId: PreventionPlanMother::registeredPreventionPlan()->id(),
            versionNumber: 0,
            locale: Locale::Spanish,
        )));
    }

    public function testGeneratesPdfForTheRequestedVersion(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();
        $plan = PreventionPlanMother::withDraftApprovedStatus('Plan Test', 'plan-test-id', 'account-id-test');
        $repository->add($plan);

        $version = $plan->currentPlanVersion();
        $generator = new SpyPreventionPlanPdfGenerator();

        $this->createSUT($repository, $generator)(
            new GeneratePdfPreventionPlanRequest($plan->id(), $version->versionNumber(), Locale::Spanish),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $generator->callCount());
        $this->assertSame($plan->id(), $generator->lastPlan()?->id());
        $this->assertSame($version->versionNumber(), $generator->lastVersion()?->versionNumber());
    }

    public function testDoesNothingWhenPlanNotFound(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();
        $missingId = PreventionPlanMother::registeredPreventionPlan()->id();

        $generator = new SpyPreventionPlanPdfGenerator();

        $this->createSUT($repository, $generator)(
            new GeneratePdfPreventionPlanRequest($missingId, 0, Locale::Spanish),
            UserMother::adminUser(),
        );

        $this->assertSame(0, $generator->callCount());
    }

    public function testDoesNothingWhenVersionNotFound(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();
        $plan = PreventionPlanMother::withDraftApprovedStatus('Plan Test', 'plan-test-id', 'account-id-test');
        $repository->add($plan);

        $generator = new SpyPreventionPlanPdfGenerator();

        $this->createSUT($repository, $generator)(
            new GeneratePdfPreventionPlanRequest($plan->id(), 999, Locale::Spanish),
            UserMother::adminUser(),
        );

        $this->assertSame(0, $generator->callCount());
    }

    private function createSUT(PreventionPlanRepository $repository, ?PreventionPlanPdfGenerator $generator = null): GeneratePdfPreventionPlanService
    {
        return new GeneratePdfPreventionPlanService(
            preventionPlanRepository: $repository,
            pdfGenerator: $generator ?? new SpyPreventionPlanPdfGenerator(),
            pdfUploader: new NullPreventionPlanPdfUploader(),
            draftTranslator: new NullPreventionPlanDraftTranslator(),
        );
    }
}
