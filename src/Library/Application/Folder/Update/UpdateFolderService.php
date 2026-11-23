<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Update;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateFolderService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly FolderRepository $repository,
        private readonly FolderAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateFolderRequest $request, User $user): UpdateFolderResponse
    {
        $folder = $this->repository->find($request->folderId);

        if (null === $folder) {
            throw new FolderNotFoundException($request->folderId);
        }

        $this->authorizer->denyUnlessCanDoThat($folder, Action::Edit, $user);

        $name = trim($request->name);

        if ($name !== $folder->name()) {
            $existing = $this->repository->findOneByAccountAndName($folder->accountId(), $name, $folder->parentId());

            if ($existing instanceof Folder && !$existing->id()->equals($folder->id())) {
                throw new FolderAlreadyExistsException($name);
            }

            $folder->rename($name);

            $this->repository->add($folder);
        }

        return new UpdateFolderResponse(
            folderId: $folder->id(),
            name: $folder->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateFolderRequest;
    }
}
