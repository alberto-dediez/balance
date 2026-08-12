<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Upload;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UploadMediaService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaStorage $storage,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(UploadMediaRequest $request, User $user): UploadMediaResponse
    {
        $id = MediaId::generate();

        $media = new Media(id: $id, accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($media, Action::Register, $user);

        // Only an administrator publishes a file in the library of every account
        if ($request->shared) {
            $this->authorizer->denyUnlessCanDoThat($media, Action::Admin, $user);
        }

        $path = $this->storage->store($id, $request->accountId, $request->extension, $request->localPath);

        $media->upload(
            name: '' === $request->name ? $request->fileName : $request->name,
            fileName: $request->fileName,
            path: $path,
            extension: $request->extension,
            mimeType: $request->mimeType,
            size: $request->size,
            accountId: $request->accountId,
            uploadedBy: $request->uploadedBy ?? $user->id(),
            description: $request->description,
            alternativeText: $request->alternativeText,
            visibility: $request->visibility,
            shared: $request->shared,
        );

        $this->repository->add($media);

        return new UploadMediaResponse(
            mediaId: $media->id(),
            name: $media->name(),
            fileName: $media->fileName(),
            path: $media->path(),
            mimeType: $media->mimeType(),
            type: $media->type(),
            size: $media->size(),
            visibility: $media->visibility(),
            shared: $media->isShared(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UploadMediaRequest;
    }
}
