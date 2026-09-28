<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Remove;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMaterialityTopicService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityTopicAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMaterialityTopicRequest $request, User $user): RemoveMaterialityTopicResponse
    {
        $materialityTopic = $this->materialityTopicRepository->find($request->materialityTopicId);

        if (null == $materialityTopic) {
            throw new MaterialityTopicNotFoundException($request->materialityTopicId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialityTopic, SecurityAction::Remove, $user);

        $materialityTopic->remove();

        $this->materialityTopicRepository->add($materialityTopic);

        return new RemoveMaterialityTopicResponse(
            materialityTopicId: $materialityTopic->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMaterialityTopicRequest;
    }
}
