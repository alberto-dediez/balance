<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Update;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateMediaService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMediaRequest $request, User $user): UpdateMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media) {
            throw new MediaNotFoundException($request->mediaId);
        }

        $this->authorizer->denyUnlessCanDoThat($media, Action::Edit, $user);

        if (
            $request->name !== $media->name()
            || $request->description !== $media->description()
            || $request->alternativeText !== $media->alternativeText()
        ) {
            $media->changeDetails($request->name, $request->description, $request->alternativeText);
        }

        if ($request->visibility !== $media->visibility()) {
            $media->changeVisibility($request->visibility);
        }

        $this->repository->add($media);

        return new UpdateMediaResponse(
            mediaId: $media->id(),
            name: $media->name(),
            description: $media->description(),
            alternativeText: $media->alternativeText(),
            visibility: $media->visibility(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMediaRequest;
    }
}
