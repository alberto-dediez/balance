<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMaterialityAssessmentOfCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialityAssessmentAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMaterialityAssessmentOfCarbonFootprintRequest $request, User $user): RemoveMaterialityAssessmentOfCarbonFootprintResponse
    {
        $materialityAssessment = $this->materialityAssessmentRepository->findCurrentByCarbonFootprint($request->subjectType, $request->carbonFootprintId);

        if (null === $materialityAssessment) {
            return new RemoveMaterialityAssessmentOfCarbonFootprintResponse(materialityAssessmentId: null);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityAssessment, SecurityAction::Remove, $user);

        $materialityAssessment->remove();

        $this->materialityAssessmentRepository->add($materialityAssessment);

        return new RemoveMaterialityAssessmentOfCarbonFootprintResponse(materialityAssessmentId: $materialityAssessment->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMaterialityAssessmentOfCarbonFootprintRequest;
    }
}
