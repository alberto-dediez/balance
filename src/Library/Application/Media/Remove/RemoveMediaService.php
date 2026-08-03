<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Remove;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMediaService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaStorage $storage,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMediaRequest $request, User $user): RemoveMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media) {
            throw new MediaNotFoundException($request->mediaId);
        }

        $this->authorizer->denyUnlessCanDoThat($media, Action::Remove, $user);

        $path = $media->path();

        $media->remove();

        $this->repository->add($media);

        $this->storage->delete($path);

        return new RemoveMediaResponse(
            mediaId: $media->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMediaRequest;
    }
}
