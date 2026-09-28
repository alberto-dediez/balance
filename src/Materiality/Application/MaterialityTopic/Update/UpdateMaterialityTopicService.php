<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Update;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMaterialityTopicService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityTopicAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMaterialityTopicRequest $request, User $user): UpdateMaterialityTopicResponse
    {
        $materialityTopic = $this->materialityTopicRepository->find($request->materialityTopicId);

        if (null == $materialityTopic) {
            throw new MaterialityTopicNotFoundException($request->materialityTopicId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityTopic, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $materialityTopic->update(
            scope: $request->scope,
            direction: $request->direction,
            name: $request->name,
            description: $request->description,
            position: $request->position,
            active: $request->active,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->materialityTopicRepository->add($materialityTopic);

        return new UpdateMaterialityTopicResponse(
            materialityTopicId: $materialityTopic->id(),
            name: $materialityTopic->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMaterialityTopicRequest;
    }
}
