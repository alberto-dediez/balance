<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Register;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNumberAlreadyUsedException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMaterialityTopicService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityTopicAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMaterialityTopicRequest $request, User $user): RegisterMaterialityTopicResponse
    {
        $id = MaterialityTopicId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MaterialityTopic(id: $id), SecurityAction::Register, $user);

        if (null !== $request->number && null !== $this->materialityTopicRepository->findByNumber($request->number)) {
            throw new MaterialityTopicNumberAlreadyUsedException($request->number);
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $materialityTopic = new MaterialityTopic($id);
        $materialityTopic->register(
            number: $request->number,
            scope: $request->scope,
            direction: $request->direction,
            name: $request->name,
            description: $request->description,
            position: $request->position,
            standard: $request->standard,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->materialityTopicRepository->add($materialityTopic);

        return new RegisterMaterialityTopicResponse(
            materialityTopicId: $materialityTopic->id(),
            name: $materialityTopic->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMaterialityTopicRequest;
    }
}
