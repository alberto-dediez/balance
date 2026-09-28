<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Translate;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionNotFoundException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class TranslateMaterialityQuestionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private MaterialityQuestionAuthorizer $authorizer,
    ) {}

    public function __invoke(TranslateMaterialityQuestionRequest $request, User $user): TranslateMaterialityQuestionResponse
    {
        $materialityQuestion = $this->materialityQuestionRepository->find($request->materialityQuestionId);

        if (null == $materialityQuestion) {
            throw new MaterialityQuestionNotFoundException($request->materialityQuestionId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityQuestion, SecurityAction::Edit, $user);

        $materialityQuestion->translate($request->text, $request->help, $request->locale);

        $this->materialityQuestionRepository->add($materialityQuestion);

        return new TranslateMaterialityQuestionResponse(
            materialityQuestionId: $materialityQuestion->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof TranslateMaterialityQuestionRequest;
    }
}
