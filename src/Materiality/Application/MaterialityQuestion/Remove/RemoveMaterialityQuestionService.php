<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Remove;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionNotFoundException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMaterialityQuestionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private MaterialityQuestionAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMaterialityQuestionRequest $request, User $user): RemoveMaterialityQuestionResponse
    {
        $materialityQuestion = $this->materialityQuestionRepository->find($request->materialityQuestionId);

        if (null == $materialityQuestion) {
            throw new MaterialityQuestionNotFoundException($request->materialityQuestionId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityQuestion, SecurityAction::Remove, $user);

        $materialityQuestion->remove();

        $this->materialityQuestionRepository->add($materialityQuestion);

        return new RemoveMaterialityQuestionResponse(
            materialityQuestionId: $materialityQuestion->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMaterialityQuestionRequest;
    }
}
