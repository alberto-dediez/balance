<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\GeneratePdf;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanDraftTranslator;
use App\FoodLoss\Application\PreventionPlan\PreventionPlanPdfUploader;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanPdfGenerator;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GeneratePdfPreventionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private PreventionPlanPdfGenerator $pdfGenerator,
        private PreventionPlanPdfUploader $pdfUploader,
        private PreventionPlanDraftTranslator $draftTranslator,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof GeneratePdfPreventionPlanRequest;
    }

    public function __invoke(GeneratePdfPreventionPlanRequest $request, User $user): null
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (!$preventionPlan instanceof PreventionPlan) {
            return null;
        }

        $version = $preventionPlan->versions()->findByVersionNumber($request->versionNumber);

        if (!$version instanceof PreventionPlanVersion) {
            return null;
        }

        $this->draftTranslator->translate($version, $request->locale);

        $tempPath = $this->pdfGenerator->generate($preventionPlan, $version, $request->locale);

        $pdfPath = $this->pdfUploader->upload($tempPath, $preventionPlan->id(), $version->versionNumber(), $request->locale);

        $preventionPlan->savePDF($pdfPath, $request->locale);
        $this->preventionPlanRepository->add($preventionPlan);

        return null;
    }
}
