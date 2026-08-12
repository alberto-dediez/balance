<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Share;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ShareMediaService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(ShareMediaRequest $request, User $user): ShareMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media) {
            throw new MediaNotFoundException($request->mediaId);
        }

        $this->authorizer->denyUnlessCanDoThat($media, Action::View, $user);

        if (!$media->visibility()->satisfies($request->visibility)) {
            $media->changeVisibility($request->visibility);

            $this->repository->add($media);
        }

        return new ShareMediaResponse(
            mediaId: $media->id(),
            visibility: $media->visibility(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ShareMediaRequest;
    }
}
