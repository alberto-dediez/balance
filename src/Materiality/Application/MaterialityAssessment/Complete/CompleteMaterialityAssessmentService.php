<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Complete;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Application\MaterialitySettings\DecisionRule\MaterialityDecisionRuleProvider;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotFoundException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CompleteMaterialityAssessmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialityAssessmentFootprints $footprints,
        private MaterialityAssessmentAuthorizer $authorizer,
        private MaterialityDecisionRuleProvider $ruleProvider,
    ) {}

    public function __invoke(CompleteMaterialityAssessmentRequest $request, User $user): CompleteMaterialityAssessmentResponse
    {
        $materialityAssessment = $this->materialityAssessmentRepository->find($request->materialityAssessmentId);

        if (null === $materialityAssessment) {
            throw new MaterialityAssessmentNotFoundException($request->materialityAssessmentId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityAssessment, SecurityAction::Edit, $user);

        $carbonFootprint = $this->footprints->find($materialityAssessment->subjectType(), $materialityAssessment->carbonFootprintId());

        $this->footprints->ensureIsEditable($carbonFootprint);

        $materialityAssessment->complete($user->id(), $this->ruleProvider->rule());

        $this->materialityAssessmentRepository->add($materialityAssessment);

        return new CompleteMaterialityAssessmentResponse(
            materialityAssessmentId: $materialityAssessment->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CompleteMaterialityAssessmentRequest;
    }
}
