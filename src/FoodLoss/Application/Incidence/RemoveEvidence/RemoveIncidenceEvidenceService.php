<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\RemoveEvidence;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Application\Incidence\IncidenceEvidenceFileRemover;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveIncidenceEvidenceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private IncidenceRepository $incidenceRepository,
        private IncidenceAuthorizer $authorizer,
        private IncidenceEvidenceFileRemover $fileRemover,
    ) {}

    public function __invoke(RemoveIncidenceEvidenceRequest $request, User $user): RemoveIncidenceEvidenceResponse
    {
        $incidence = $this->incidenceRepository->find($request->incidenceId);

        if (null === $incidence) {
            throw new \InvalidArgumentException('Incidence not found');
        }

        $this->authorizer->denyUnlessCanDoThat($incidence, SecurityAction::Edit, $user);

        $evidence = $incidence->evidences()->findById($request->evidenceId);

        $incidence->removeEvidence($request->evidenceId);

        $this->incidenceRepository->add($incidence);

        if (null !== $evidence) {
            $this->fileRemover->remove($evidence->path());
        }

        return new RemoveIncidenceEvidenceResponse(evidenceId: $request->evidenceId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveIncidenceEvidenceRequest;
    }
}
