<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\GenerateSections;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\SectionsGenerator;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GenerateSectionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private SectionsGenerator $sectionsGenerator,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof GenerateSectionsRequest;
    }

    public function __invoke(GenerateSectionsRequest $request, User $user): null
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (!$preventionPlan instanceof PreventionPlan) {
            return null;
        }

        $version = $preventionPlan->currentPlanVersion();

        $this->sectionsGenerator->generate($version);

        $preventionPlan->markCurrentVersionAsGenerated();
        $this->preventionPlanRepository->add($preventionPlan);

        return null;
    }
}
