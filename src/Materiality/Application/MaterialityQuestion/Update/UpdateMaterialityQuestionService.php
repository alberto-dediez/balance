<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Update;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionNotFoundException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMaterialityQuestionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityQuestionAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMaterialityQuestionRequest $request, User $user): UpdateMaterialityQuestionResponse
    {
        $materialityQuestion = $this->materialityQuestionRepository->find($request->materialityQuestionId);

        if (null == $materialityQuestion) {
            throw new MaterialityQuestionNotFoundException($request->materialityQuestionId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityQuestion, SecurityAction::Edit, $user);

        if (null !== $request->topicId && null === $this->materialityTopicRepository->find($request->topicId)) {
            throw new MaterialityTopicNotFoundException($request->topicId);
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $materialityQuestion->update(
            criterion: $request->criterion,
            topicId: $request->topicId,
            text: $request->text,
            help: $request->help,
            answerScale: $request->answerScale,
            weight: $request->weight,
            position: $request->position,
            active: $request->active,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->materialityQuestionRepository->add($materialityQuestion);

        return new UpdateMaterialityQuestionResponse(
            materialityQuestionId: $materialityQuestion->id(),
            text: $materialityQuestion->text(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMaterialityQuestionRequest;
    }
}
