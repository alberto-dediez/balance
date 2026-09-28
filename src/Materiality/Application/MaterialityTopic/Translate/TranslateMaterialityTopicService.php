<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Translate;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class TranslateMaterialityTopicService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityTopicAuthorizer $authorizer,
    ) {}

    public function __invoke(TranslateMaterialityTopicRequest $request, User $user): TranslateMaterialityTopicResponse
    {
        $materialityTopic = $this->materialityTopicRepository->find($request->materialityTopicId);

        if (null == $materialityTopic) {
            throw new MaterialityTopicNotFoundException($request->materialityTopicId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityTopic, SecurityAction::Edit, $user);

        $materialityTopic->translate($request->name, $request->description, $request->locale);

        $this->materialityTopicRepository->add($materialityTopic);

        return new TranslateMaterialityTopicResponse(
            materialityTopicId: $materialityTopic->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof TranslateMaterialityTopicRequest;
    }
}
