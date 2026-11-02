<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Reopen;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotFoundException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReopenMaterialityAssessmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialityAssessmentFootprints $footprints,
        private MaterialityAssessmentAuthorizer $authorizer,
    ) {}

    public function __invoke(ReopenMaterialityAssessmentRequest $request, User $user): ReopenMaterialityAssessmentResponse
    {
        $materialityAssessment = $this->materialityAssessmentRepository->find($request->materialityAssessmentId);

        if (null === $materialityAssessment) {
            throw new MaterialityAssessmentNotFoundException($request->materialityAssessmentId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityAssessment, SecurityAction::Edit, $user);

        $carbonFootprint = $this->footprints->find($materialityAssessment->subjectType(), $materialityAssessment->carbonFootprintId());

        $this->footprints->ensureIsEditable($carbonFootprint);

        $materialityAssessment->reopen();

        $this->materialityAssessmentRepository->add($materialityAssessment);

        return new ReopenMaterialityAssessmentResponse(
            materialityAssessmentId: $materialityAssessment->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReopenMaterialityAssessmentRequest;
    }
}
