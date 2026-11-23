<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Register;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNestingNotAllowedException;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterFolderService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly FolderRepository $repository,
        private readonly FolderAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterFolderRequest $request, User $user): RegisterFolderResponse
    {
        $folder = new Folder(id: FolderId::generate(), accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($folder, Action::Register, $user);

        $name = trim($request->name);

        if ($request->parentId instanceof FolderId) {
            $this->assertValidParent($request->parentId, $request->accountId);
        }

        if ($this->repository->findOneByAccountAndName($request->accountId, $name, $request->parentId) instanceof Folder) {
            throw new FolderAlreadyExistsException($name);
        }

        $folder->register($name, $request->accountId, $request->parentId);

        $this->repository->add($folder);

        return new RegisterFolderResponse(
            folderId: $folder->id(),
            name: $folder->name(),
            parentId: $folder->parentId(),
        );
    }

    private function assertValidParent(FolderId $parentId, ?AccountId $accountId): void
    {
        $parent = $this->repository->find($parentId);

        if (!$parent instanceof Folder || !$this->sameAccount($parent->accountId(), $accountId)) {
            throw new FolderNotFoundException($parentId);
        }

        if ($parent->isSubfolder()) {
            throw new FolderNestingNotAllowedException($parentId);
        }
    }

    private function sameAccount(?AccountId $parentAccountId, ?AccountId $accountId): bool
    {
        if (!$parentAccountId instanceof AccountId || !$accountId instanceof AccountId) {
            return !$parentAccountId instanceof AccountId && !$accountId instanceof AccountId;
        }

        return $parentAccountId->equals($accountId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterFolderRequest;
    }
}
