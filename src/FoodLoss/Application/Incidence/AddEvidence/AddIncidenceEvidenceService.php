<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\AddEvidence;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Application\Incidence\IncidenceEvidenceUploader;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AddIncidenceEvidenceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private IncidenceRepository $incidenceRepository,
        private IncidenceAuthorizer $authorizer,
        private IncidenceEvidenceUploader $uploader,
    ) {}

    public function __invoke(AddIncidenceEvidenceRequest $request, User $user): AddIncidenceEvidenceResponse
    {
        $incidence = $this->incidenceRepository->find($request->incidenceId);

        if (null === $incidence) {
            throw new \InvalidArgumentException('Incidence not found');
        }

        $this->authorizer->denyUnlessCanDoThat($incidence, SecurityAction::Edit, $user);

        $evidenceId = IncidenceEvidenceId::generate();

        $path = $this->uploader->upload(
            sourcePath: $request->temporaryFilePath,
            extension: $request->fileExtension,
            accountId: $request->accountId,
            incidenceId: $request->incidenceId,
        );

        $incidence->addEvidence(
            evidenceId: $evidenceId,
            path: $path,
            originalName: $request->originalName,
            mimeType: $request->mimeType,
        );

        $this->incidenceRepository->add($incidence);

        return new AddIncidenceEvidenceResponse(
            evidenceId: $evidenceId,
            path: $path,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AddIncidenceEvidenceRequest;
    }
}
