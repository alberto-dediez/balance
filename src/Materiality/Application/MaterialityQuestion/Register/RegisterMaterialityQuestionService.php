<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Register;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
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

class RegisterMaterialityQuestionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityQuestionAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMaterialityQuestionRequest $request, User $user): RegisterMaterialityQuestionResponse
    {
        $id = MaterialityQuestionId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MaterialityQuestion(id: $id), SecurityAction::Register, $user);

        if (null !== $request->topicId && null === $this->materialityTopicRepository->find($request->topicId)) {
            throw new MaterialityTopicNotFoundException($request->topicId);
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $materialityQuestion = new MaterialityQuestion($id);
        $materialityQuestion->register(
            criterion: $request->criterion,
            topicId: $request->topicId,
            text: $request->text,
            help: $request->help,
            answerScale: $request->answerScale,
            weight: $request->weight,
            position: $request->position,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->materialityQuestionRepository->add($materialityQuestion);

        return new RegisterMaterialityQuestionResponse(
            materialityQuestionId: $materialityQuestion->id(),
            text: $materialityQuestion->text(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMaterialityQuestionRequest;
    }
}
